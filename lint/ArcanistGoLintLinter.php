<?php

/**
 * A wrapper for golint linter, heavily inspired by ArcanistCoffeeLintLinter.
 */
final class ArcanistGoLintLinter extends ArcanistLinter {

  const GOLINT_ERROR = 1;
  const GOLINT_WARNING = 2;

  public function getLinterName() {
    return 'GoLint';
  }

  public function getLinterConfigurationName() {
    return 'golint';
  }

  public function getLintSeverityMap() {
    return array(
      self::GOLINT_ERROR => ArcanistLintSeverity::SEVERITY_ERROR,
      self::GOLINT_WARNING => ArcanistLintSeverity::SEVERITY_WARNING
    );
  }

  public function getLintNameMap() {
    return array(
      self::GOLINT_ERROR => "golint Error",
      self::GOLINT_WARNING => "golint Warning"
    );
  }

  public function getGoLintOptions() {
    return "";
  }

  private function getGoLintBin() {
    $working_copy = $this->getEngine()->getWorkingCopy();
    $prefix = $working_copy->getConfig('lint.golint.prefix');
    $bin = $working_copy->getConfig('lint.golint.bin');

    if ($bin === null) {
      $bin = "golint";
    }

    if ($prefix !== null) {
      $bin = $prefix."/".$bin;

      if (!Filesystem::pathExists($bin)) {
        throw new ArcanistUsageException(
          "Unable to find golint binary in a specified directory. Make sure ".
          "that 'lint.golint.prefix' and 'lint.golint.bin' keys are set ".
          "correctly. If you'd rather use a copy of golint installed ".
          "globally, you can just remove these keys from your .arcconfig");
      }

      return $bin;
    }

    // Look for globally installed golint
    list($err) = (phutil_is_windows()
      ? exec_manual('where %s', $bin)
      : exec_manual('which %s', $bin));

    if ($err) {
      throw new ArcanistUsageException(
        "golint does not appear to be installed on this system. Install it ".
        "(e.g., with 'npm install golint -g') or configure ".
        "'lint.golint.prefix' in your .arcconfig to point to the directory ".
        "where it resides.");
    }

    return $bin;
  }

  public function willLintPaths(array $paths) {
    if (!$this->isCodeEnabled(self::GOLINT_ERROR) &&
        !$this->isCodeEnabled(self::GOLINT_WARNING)) {
      return;
    }

    $golint_bin = $this->getGoLintBin();
    $golint_options = $this->getGoLintOptions();
    $futures = array();

    foreach ($paths as $path) {
      $filepath = $this->getEngine()->getFilePathOnDisk($path);
      $futures[$path] = new ExecFuture(
        "%s %s %C",
        $golint_bin,
        $filepath,
        $golint_options);
    }

    foreach (Futures($futures)->limit(8) as $path => $future) {
      $this->results[$path] = $future->resolve();
    }
  }

  public function lintPath($path) {
    if (!$this->isCodeEnabled(self::GOLINT_ERROR) &&
        !$this->isCodeEnabled(self::GOLINT_WARNING)) {
      return;
    }

    list($rc, $stdout, $stderr) = $this->results[$path];

    if ($rc !== 0) {
      // golint exited with an error
      throw new ArcanistUsageException(
        "golint exited with an error.\n".
        "stdout:\n\n{$stdout}".
        "stderr:\n\n{$stderr}");
    }

    $errors = explode("\n", $stdout);

    foreach ($errors as $err) {
      if (!strlen($err)) {
        continue;
      }

      if (preg_match("/(.+):(\d+):(\d+): (.+)$/", $err, $fields)) {
        $this->raiseLintAtLine(
          $fields[2],
          $fields[3],
          self::GOLINT_WARNING,
          $fields[4]);
      }
    }
  }
}
