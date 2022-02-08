<?php

namespace PHRouter;

require __DIR__."/Phemplate.php";
class Response
{
    private Phemplate $temp;
    private string $engExt;

    /**
     * setting engine extension
     * @param $ext
     * @return $this
     */
    public function engineExt($ext): Response
    {
        $this->engExt = $ext;
        return $this;
    }
    /**
     * Default Constructor
     * @method __construct
     */
    public function __construct()
    {
        $this->engExt = 'phemplate';
        $this->temp = new Phemplate();
    }

    /**
     * Send Plaintext
     * @method send
     * @param string $data Plaintext message to be output
     * @param int $status HTTP code
     * @return int            Indication if printing was successful
     */
    public function send(string $data, int $status = 200): int
    {
        self::status($status);
        return print($data);
    }

    /**
     * Send Plaintext
     * @method send
     * @param string $file output text inside any file
     * @param int $status HTTP code
     * @return int            Indication if printing was successful
     */
    public function sendFile(string $file, int $status = 200): int
    {
        self::status($status);
        $file = "..\\views\\$file";
        return print(Phemplate::accessFile($file));
    }

    /**
     * Send as JSON
     * @method json
     * @param array $data PHP array to be output in JSON format
     * @param int|null $status HTTP code
     * @return int            Indication if printing was successful
     */
    public function json(array $data, int $status = null): int
    {
        self::status($status);
        return print(json_encode($data));
    }

    /**
     * Return HTTP status only
     * @method status
     * @param int|null $status HTTP code
     * @return int         indication if printing was successful
     */
    public static function status(int $status = null): int
    {
        if (isset($status)) {
            http_response_code($status);
            return 1;
        }
        return 0;
    }

    /**
     * @param string $viewFile
     * @param array $vars
     * @return int
     */
    public function render(string $viewFile, array $vars): int
    {
        $this->temp->setExt($this->engExt);
        $this->temp->setVarsArray($vars);
        self::status(200);
        return print($this->temp->templateRep($viewFile));
    }
}
