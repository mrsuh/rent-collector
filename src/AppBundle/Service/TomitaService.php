<?php

namespace AppBundle\Service;

class TomitaService
{
    protected $bin;
    protected $config;

    public function __construct($bin, $config)
    {
        $this->bin    = $bin;
        $this->config = $config;
    }

    /**
     * @param      $text
     * @param bool $debug
     * @return null|string
     */
    public function run($text, $debug = false)
    {
        $cmd = $this->bin . ' ' . $this->config;

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        $out     = null;
        $err     = null;
        if (is_resource($process)) {
            fwrite($pipes[0], $text);
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);
        }

        if ($debug) {
            print_r($err);
            print_r($out);
        }

        return $out;
    }

    /**
     * @param $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }
}