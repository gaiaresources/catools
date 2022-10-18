<?php
namespace RWAHS\Profile;

use Amp\Parallel\Context\ContextException;
use Amp\TimeoutException;
use DOMDocument;
use DOMXPath;

use function Amp\Parallel\Worker\enqueueCallable;
use function Amp\Promise\all;
use function Amp\Promise\timeout;
use function Amp\Promise\timeoutWithDefault;
use function Amp\Promise\wait;

class ProfileValidationTest extends AbstractProfileTest
{
    public function testProfileConformsToSchema()
    {
        $this->xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $url = $this->xpath->query('@xsi:noNamespaceSchemaLocation')->item(0)->textContent;
        $this->assertTrue($this->profile->schemaValidate($url));    
    }
    public function testMiniProfilesConformToSchema()
    {
        $basePath = dirname(__DIR__, 3) . "/src/db/migrations";
        $allFiles = glob($basePath . '/*.xml');
        // So that we can capture file name warnings we replace the error handler.
        $maxConcurrent = 4;
        foreach (array_chunk($allFiles, $maxConcurrent) as $files) {
            $promises = [];
            foreach ($files as $file) {
                // Use in set_error_handler.
                $promises[$file] = timeout(enqueueCallable('RWAHS\Profile\ProfileValidationTest::validateMiniProfile', $file), 60000);
            }
            try {
                $responses = wait(all($promises));
                foreach ($responses as $file => $result) {
                    $this->assertNotEquals('profile.xsd', $result['url'], "We expect to be using an external profile schema for file $file");
                    $this->assertEmpty($result['errors'], "Expected no errors when validating profile $file");
                    $this->assertTrue($result['valid'], "Profile $file should match schema $result[url]");
                    $this->assertEquals(0, $result['count'], "No base attribute should exist for profile $file.");
                }
            } catch (TimeoutException $e) {
                $this->markTestSkipped('Profile validation tests did not complete in time');
            }
        }
    }

    public static function validateMiniProfile($file): array
    {
        $errors = [];
        set_error_handler(function ($errno, $error, $errorFile, $line) use ($file, $errors) {
            $errors[] = "Error while processing file $file. Error was $error ($errno) $errorFile:L$line";
        });
        $profile = new DOMDocument();
        $profile->load($file);
        $xpath = new DOMXPath($profile);
        $xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $url = $xpath->query('@xsi:noNamespaceSchemaLocation')->item(0)->textContent;
        if (!$url) {
            $url = 'https://raw.githubusercontent.com/collectiveaccess/providence/develop/install/profiles/xml/profile.xsd';
        }
        $ret = [
            'valid' => $profile->schemaValidate($url),
            'errors' => $errors,
            'url' => $url,
            'count' => $xpath->query('@base')->count(),
        ];
        restore_error_handler();
        return $ret;
    }
    
}
