<?php

/**
 * A wrapper for govet linter, heavily inspired by ArcanistCoffeeLintLinter.
 */
final class ArcanistGoVetLinter extends ArcanistLinter {

  const GOLINT_ERROR = 1;
  const GOLINT_WARNING = 2;

  public function getLinterName() {
    return 'GoVet';
  }

  public function getLinterConfigurationName() {
    return 'govet';
  }

  public function getLintSeverityMap() {
    return array(
      self::GOLINT_ERROR => ArcanistLintSeverity::SEVERITY_ERROR,
      self::GOLINT_WARNING => ArcanistLintSeverity::SEVERITY_WARNING
    );
  }

  public function getLintNameMap() {
    return array(
      self::GOLINT_ERROR => "govet Error",
      self::GOLINT_WARNING => "govet Warning"
    );
  }

  public function getGoVetOptions() {
    return "";
  }

  private function getGoVetBin() {
    return "go";
  }

  public function willLintPaths(array $paths) {
    if (!$this->isCodeEnabled(self::GOLINT_ERROR) &&
        !$this->isCodeEnabled(self::GOLINT_WARNING)) {
      return;
    }

    $govet_bin = $this->getGoVetBin();
    $govet_options = $this->getGoVetOptions();
    $futures = array();

    foreach ($paths as $path) {
      $filepath = $this->getEngine()->getFilePathOnDisk($path);
      $futures[$path] = new ExecFuture(
        "%s vet %s %C",
        $govet_bin,
        $filepath,
        $govet_options);
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

    // if ($rc !== 0) {
    //   // govet exited with an error
    //   throw new ArcanistUsageException(
    //     "govet exited with an error.\n".
    //     "stdout:\n\n{$stdout}".
    //     "stderr:\n\n{$stderr}");
    // }

    $errors = explode("\n", $stderr);

    foreach ($errors as $err) {
      if (!strlen($err)) {
        continue;
      }

      if (preg_match("/(.+):(\d+): (.+)$/", $err, $fields)) {
        $this->raiseLintAtLine(
          $fields[2],
          null,
          self::GOLINT_WARNING,
          $fields[3]);
      }
    }
  }
}
