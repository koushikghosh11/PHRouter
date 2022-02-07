<?php

namespace PHRouter;

const blankStr = "";

class Phemplate
{
    private string $viewsDir;
    private string $layoutsDir;
    private string $partialsDir;
    private string $ext;
    private array $varsArray;

    public function __construct()
    {
        $this->viewsDir = $_SERVER['DOCUMENT_ROOT'] . "\\views";
        $this->layoutsDir = "layouts";
        $this->partialsDir = "partials";
        $this->ext = "phemplate";
    }

    /**
     * @param array $varsArray
     * @return Phemplate
     */
    public function setVarsArray(array $varsArray): Phemplate
    {
        $this->varsArray = $varsArray;
        return $this;
    }

    /** set layouts directory
     * @param string $layoutsDir
     * @return Phemplate
     */
    public function setLayoutsDir(string $layoutsDir): Phemplate
    {
        $this->layoutsDir = $layoutsDir;
        return $this;
    }

    /** set partials Directory
     * @param string $partialsDir
     * @return Phemplate
     */
    public function setPartialsDir(string $partialsDir): Phemplate
    {
        $this->partialsDir = $partialsDir;
        return $this;
    }

    /** set extension of template files
     * @param string $ext
     * @return Phemplate
     */
    public function setExt(string $ext): Phemplate
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * read or edit file from path, default mode is read
     * @param string $path
     * @param string $mode
     * @return bool|string
     */
    public static function accessFile(string $path, string $mode = 'r')
    {
        if (!is_file($path)) return blankStr;
        return fread(fopen($path, $mode), filesize($path));
    }

    /** read text inside template file
     * @param $path
     * @return bool|string
     */
    private function readTempFile($path)
    {
        $fileName = $this->viewsDir . "\\" . $path . "." . $this->ext;
//        echo $fileName.br;
        return self::accessFile($fileName);
    }

    /**
     * @param $str
     * @return mixed
     */
    private function tempPartial($str)
    {
        $pattern = "/(?'replPart'{{>(?'part'\w+)}})/s";
        $result = preg_match($pattern, $str, $match, PREG_UNMATCHED_AS_NULL);
        if (!$result) return $str;
        $str = preg_replace("/" . $match['replPart'] . "/s", self::readTempFile($this->partialsDir . "\\" . $match['part']), $str);
        return self::tempPartial($str);
    }

    /**
     * @param $string
     * @param string $layout
     * @return array|string|null
     */
    private function layoutStrRep($string, string $layout)
    {
        $layoutStr = self::readTempFile($this->layoutsDir . "\\" . $layout);
        if ($layoutStr == blankStr) return $string;
        return preg_replace("@{{{body}}}@mi", $string, $layoutStr);
    }

    /**
     * @param $str
     * @return mixed
     */
    private function tempStr($str)
    {
        $pattern = "/(?'tStr'{{(?'str'\w+)}})/s";
        $result = preg_match($pattern, $str, $match, PREG_UNMATCHED_AS_NULL);
        if (!$result) return $str;
        $array = $this->varsArray;
        $matchStr = $match['str'];
        //checking if the variable is set in array and if variable is boolean then return "true" for true and "false" for false
        $matchStrVal = isset($array[$matchStr]) ? (is_bool($array[$matchStr]) ? ($array[$matchStr] ? "true" : "false") : $array[$matchStr]) : blankStr;
        $str = preg_replace("/" . $match['tStr'] . "/s", $matchStrVal, $str);
        return self::tempStr($str);
    }

    /**
     * @param $str
     * @return mixed
     */
    private function tempFLoop($str)
    {
        $pattern = "@(?'loop'{\s*foreach\s+from=(?'array'\w+)\s+(?:(?'key'\w+)\s*=>\s*)?(?'value'\w+)\s*}\n?(?'area'.*?)\n?{\s*/\s*foreach\s*})@s";
        $result = preg_match($pattern, $str, $match, PREG_UNMATCHED_AS_NULL);
        if (!$result) return $str;
        $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
        $array = $this->varsArray;
        $rStr = blankStr;
        $matchKey = $match['key'] ?? blankStr;
        $matchVal = $match['value'] ?? blankStr;
        $matchArea = trim($match['area'] ?? blankStr);
        $arr = $array[$match['array']] ?? false;
        if ($arr && $matchVal != null) {
            if ($matchKey == null) {
                foreach ($arr as $val) {
                    $rStr .= preg_replace("/{" . $matchVal . "}/s", $val, $matchArea);
                }
            } else {
                foreach ($arr as $key => $val) {
                    $rStr .= preg_replace(array("/{" . $matchKey . "}/s", "/{" . $matchVal . "}/s"), array($key, $val), $matchArea);
                }
            }
        }
        $str = preg_replace('(' . $match['loop'] . ')', $rStr, $str);
        return self::tempFLoop($str);
    }

    /**
     * @param $str
     * @return mixed
     */
    private function tempIfElse($str)
    {
        $pattern = "@(?'ifElse'{\s*if\s+(?'bool'\w+)\s*}(?'area'.*?){\s*/\s*if\s*})@s";
        $result = preg_match($pattern, $str, $match, PREG_UNMATCHED_AS_NULL);
        if (!$result) return $str;
        $array = $this->varsArray;
        $matchIfElse = $match['ifElse'];
        $matchBool = $array[$match['bool']] ?? false;
        //checking if the variable is set in array and if variable is boolean , if not considering false
        $bool = is_bool($matchBool) ? $matchBool : false;
        //if $bool is true $matchArea is the code inside if statement otherwise ""
        $matchArea = $bool ? trim($match['area'] ?? blankStr) : blankStr;
        $str = preg_replace("@(" . $matchIfElse . ")@s", $matchArea, $str);
        return self::tempIfElse($str);
    }

    /**
     * @param $file
     * @return string|null
     */
    public function templateRep($file): ?string
    {
        $string = self::tempFLoop(self::tempIfElse(self::tempStr(self::tempPartial($this->convRoundBrackets2htmlEntities(htmlentities(self::readTempFile($file)))))));
        if (preg_match("@^&lt;!doctype html&gt;.*&lt;/html&gt;$@msi", $string)) {
            return html_entity_decode($string);
        } else {
            $layout = $this->varsArray['layout'] ?? 'main';
            return html_entity_decode(self::layoutStrRep($string, $layout));
        }
    }

    private function convRoundBrackets2htmlEntities($str)
    {
        $str = str_replace('(', '&#40;', $str);
        return str_replace(')', '&#41;', $str);
    }
}
