<?php

namespace Phlite\Test;

interface TestReportOutput {
    function reportPass(Testable $test);
    function reportFail(Fail $fail, Testable $test);
    function reportWarning(Warning $warn, Testable $test);
}