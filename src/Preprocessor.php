<?php

namespace iggyvolz\phppreproccessor;

use Composer\Semver\Semver;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

class Preprocessor
{
    private string $file;
    public function __construct(string $file)
    {
        $this->file = $file;
    }
    private function checkSatisfied(string $package, ?string $constraint):bool
    {
        if($package === "php") {
            return Semver::satisfies(phpversion(), $constraint);
        } elseif(strpos($package, "/") === false) {
            return extension_loaded($package) && (is_null($constraint) || Semver::satisfies(phpversion($package), $constraint));
        } else {
            return InstalledVersions::isInstalled($package) && (is_null($constraint) || InstalledVersions::satisfies(new VersionParser, $package, $constraint));
        }
    }
    public function __toString():string
    {
        ob_start();
        $output = [true];
        foreach(file($this->file) as $lineno => $line) {
            $lineno++;
            if(preg_match("/^#IF/", $line)) {
                $expl = explode(" ", trim($line));
                $pkg = $expl[1] ?? null;
                $constraint = $expl[2] ?? null;
                if(is_null($pkg)) {
                    throw new PreprocessorException("No package found");
                }
                $output[] = $this->checkSatisfied($pkg, $constraint);
            } elseif(preg_match("/^#ELSE/", $line)) {
                $output[count($output)-1] = !($output[count($output)-1]);
            } elseif(preg_match("/^#ERROR/", $line)) {
                if($output[count($output)-1]) {
                    throw new PreprocessorException(trim(substr($line, 7)), $this->file, $lineno);
                }
            } elseif(preg_match("/^#ENDIF/", $line)) {
                array_pop($output);
                if(empty($output)) {
                    throw new PreprocessorException("Attempted to #ENDIF without a matching condition", $this->file, $lineno);
                }
            } elseif($output[count($output)-1]) {
                echo $line;
            }
        }
        if(count($output) !== 1) {
            throw new PreprocessorException("Ended without closing all ifs", $this->file, $lineno);
        }
        return ob_get_clean();
    }
    public function saveTo(string $outputFile)
    {
        file_put_contents($outputFile, $this->__toString());
    }
}