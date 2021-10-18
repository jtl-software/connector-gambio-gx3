<?php

namespace jtl\Connector\Gambio\Controller;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Core\Utilities\Language;
use Jtl\Connector\XtcComponents\AbstractController;

/**
 * Class AbstractController
 * @package jtl\Connector\Modified\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return 'jtl\\Connector\\Gambio';
    }

    /**
     * @param array $i18ns
     * @param $shortCode
     * @return false|mixed
     * @throws LanguageException
     */
    public static function findI18n(array $i18ns, $shortCode)
    {
        $returnI18n = reset($i18ns);
        $langIso = Language::convert($shortCode);

        foreach ($i18ns as $i18n) {
            if (!method_exists($i18n, 'getLanguageISO')) {
                throw new \RuntimeException('Given element does not seem to be a valid i18n object!');
            }

            if ($i18n->getLanguageISO() === $langIso) {
                $returnI18n = $i18n;
                break;
            }
        }

        return $returnI18n;
    }

    /**
     * @param string $pattern
     * @return bool
     */
    public static function resetCache($pattern = "*")
    {
        $cacheDir = sprintf('%s/cache/', dirname(CONNECTOR_DIR));

        $cacheFiles = glob($cacheDir.$pattern);
        if (is_array($cacheFiles) === false) {
            return true;
        }

        foreach ($cacheFiles as $cacheFile) {
            unlink($cacheFile);
        }

        return true;
    }
}
