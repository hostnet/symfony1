<?php
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../../../../../lib/plugins/sfCompat10Plugin/lib/config/sfValidatorConfigHandler.class.php');

/**
 * @covers sfValidatorConfigHandler
 */
class sfValidatorConfigHandlerTest extends TestCase
{
    public function testExecute()
    {
        $handler = new sfValidatorConfigHandler();
        $handler->initialize();

        $dir = __DIR__ . '/../../../../../../lib/plugins/sfCompat10Plugin/test/unit/config/fixtures/sfValidatorConfigHandler/';

        // standard format
        $files         = array($dir.'standard_format.yml');
        $standard_data = $handler->execute($files);
        $standard_data = preg_replace('#date\: \d+/\d+/\d+ \d+\:\d+\:\d+#', '', $standard_data);

        // alternate format
        $files          = array($dir.'alternate_format.yml');
        $alternate_data = $handler->execute($files);
        $alternate_data = preg_replace('#date\: \d+/\d+/\d+ \d+\:\d+\:\d+#', '', $alternate_data);

        self::assertSame($standard_data, $alternate_data, 'standard and alternate format of validate.yml files produce the same output');
    }
}
