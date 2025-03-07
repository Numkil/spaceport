<?php

namespace Spaceport\Model;

use Symfony\Component\Yaml\Parser;

class Shuttle
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $server;

    /**
     * @var DatabaseConnection[]
     */
    private $databases;

    /**
     * @var string
     */
    private $documentRoot;

    /**
     * @var string
     */
    private $phpVersion;

    /**
     * @var string
     */
    private $elasticsearchVersion;

    /** @var string */
    private $mysqlVersion = '5.7';

    /**
     * @var string
     */
    private $nodeVersion;

    /**
     * @var bool
     */
    private $sslEnabled;

    public function __construct()
    {
        $this->name = $this->getProjectName();
        $this->databases = [];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return null !== $this->server ? $this->server : '';
    }

    /**
     * @param bool|string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return bool
     */
    public function hasServer()
    {
        return null !== $this->server;
    }

    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * @return string
     */
    public function getPhpVersion()
    {
        return $this->phpVersion;
    }

    /**
     * @param string $phpVersion
     */
    public function setPhpVersion($phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    /**
     * @return string
     */
    public function getElasticsearchVersion()
    {
        return $this->elasticsearchVersion;
    }

    /**
     * @param string $elasticsearchVersion
     * @return Shuttle
     */
    public function setElasticsearchVersion($elasticsearchVersion)
    {
        $this->elasticsearchVersion = $elasticsearchVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeVersion()
    {
        return $this->nodeVersion;
    }

    /**
     * @param string $nodeVersion
     * @return $this
     */
    public function setNodeVersion($nodeVersion)
    {
        $this->nodeVersion = $nodeVersion;

        return $this;
    }

    /**
     * @return DatabaseConnection[]
     */
    public function getDatabases()
    {
        return $this->databases;
    }

    /**
     * @param DatabaseConnection[] $databases
     * @return $this
     */
    public function setDatabases($databases)
    {
        $this->databases = $databases;

        return $this;
    }


    /**
     * @param DatabaseConnection $connection
     */
    public function addDatabaseConnection(DatabaseConnection $connection)
    {
       if(null !== $connection) {
           $this->databases[] = $connection;
       }
    }

    /**
     * @param DatabaseConnection $connection
     */
    public function removeDatabaseConnection(DatabaseConnection $connection)
    {
        if($index = array_search($connection, $this->databases, true)) {
            array_splice($this->databases, $index, 1);
        }
    }

    /**
     * @return boolean
     */
    public function sslEnabled()
    {
        return $this->sslEnabled;
    }

    /**
     * @param boolean $sslEnabled
     */
    public function setSslEnabled($sslEnabled)
    {
        $this->sslEnabled = $sslEnabled;
    }

    public function getMysqlVersion()
    {
        return $this->mysqlVersion;
    }

    public function setMysqlVersion($mysqlVersion)
    {
        $this->mysqlVersion = $mysqlVersion;

        return $this;
    }

    /**
     * Returns the project_name defined in .deploy/config.yml file
     * or the basename of the cwd if the .deploy/config.yml file does not exist
     *
     * @return string
     */
    private function getProjectName()
    {
        if (file_exists('.deploy/config.yml')) {
            $yaml = new Parser();
            $config = $yaml->parse(file_get_contents('.deploy/config.yml'));
            if (isset($config['project_name'])) {
                return $config['project_name'];
            }
        }

        return basename(getcwd());
    }
}
