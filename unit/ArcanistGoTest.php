<?php

final class ArcanistGoTestEngine extends ArcanistBaseUnitTestEngine {
  /**
   * Main entry point for the test engine.  Determines what assemblies to
   * build and test based on the files that have changed.
   *
   * @return array   Array of test results.
   */
  public function run() {
    $future = new ExecFuture("go test ./...");
    list($rc, $stdout, $stderr) = $future->resolve();
    $results = $this->parseTestResult($stdout);
    if (count($results) == 0 && $rc !== 0) {
      // go test exited with an error
      throw new ArcanistUsageException(
        "'go test' exited with an error.\n".
        "stdout:\n\n{$stdout}".
        "stderr:\n\n{$stderr}");
    }
    return $results;
  }

  private function parseTestResult($output) {
    $results = array();
    $lines = explode("\n", $output);
    $filename = "";
    $lineNum = "";
    $test = "";
    $duration = "";
    foreach ($lines as $line) {
      if (preg_match("/--- FAIL: (.+) \((.+)\)/", $line, $fields)) {
        $test = $fields[1];
        $duration = $fields[2];
      } else if (preg_match("/Location:\s*(.+):(\d+)/", $line, $fields)) {
        $filename = $fields[1];
        $lineNum = $fields[2];
      } else if (preg_match("/Error:\s*(.+)$/", $line, $fields)) {
        $error = $fields[1];

        $result = new ArcanistUnitTestResult();
        $result->setName($test . " " . $filename . ":" . $lineNum);
        $result->setResult(ArcanistUnitTestResult::RESULT_FAIL);
        $result->setUserData($error);
        $results[] = $result;
      }
    }
    return $results;
  }
}