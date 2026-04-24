<?php
namespace OCA\NCDownloader\Db;
use OCA\NCDownloader\Tools\Helper as ToolsHelper;
use OCP\DB\QueryBuilder\IQueryBuilder;

class Helper
{
    //@var OC\DB\ConnectionAdapter
    private $conn;
    private $table = "ncdownloader_info";
    private $prefixedTable;
    public function __construct()
    {
        $this->conn = \OC::$server->get(\OCP\IDBConnection::class);
        $this->prefixedTable = $this->conn->getQueryBuilder()->getTableName($this->table);
        //$container = \OC::$server->query(\OCP\IServerContainer::class);
        //ToolsHelper::debug(get_class($container->query(\OCP\RichObjectStrings\IValidator::class)));
        //$this->conn = \OC::$server->query(Connection::class);//working only with 22
        //$this->connAdapter = \OC::$server->getDatabaseConnection();
        //$this->conn = $this->connAdapter->getInner();
    }

    public function insert($insert)
    {
        $inserted = (bool) $this->conn->insertIfNotExist('*PREFIX*' . $this->table, $insert, [
            'gid',
        ]);
        return $inserted;
    }
    public function getAll()
    {
        $queryBuilder = $this->conn->getQueryBuilder()
            ->select('filename', 'type', 'gid', 'timestamp', 'status')
            ->from($this->table)
            ->executeQuery();
        return $queryBuilder->fetchAllAssociative();
    }

    public function getByUid($uid)
    {
        $queryBuilder = $this->conn->getQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('uid = :uid');
        $queryBuilder->setParameter('uid', $uid, IQueryBuilder::PARAM_STR);
        $result = $queryBuilder->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function getUidByGid($gid)
    {
        $queryBuilder = $this->conn->getQueryBuilder()
            ->select('uid')
            ->from($this->table)
            ->where('gid = :gid');
        $queryBuilder->setParameter('gid', $gid, IQueryBuilder::PARAM_STR);
        $result = $queryBuilder->executeQuery();
        return $result->fetchOne();
    }

    public function getYtdlByUid($uid)
    {
        $qb = $this->conn->getQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('uid = :uid')
            ->andWhere('type = :type')
            ->orderBy('id', 'DESC');
        $qb->setParameter('uid', $uid, IQueryBuilder::PARAM_STR);
        $qb->setParameter('type', ToolsHelper::DOWNLOADTYPE['YOUTUBE-DL'], IQueryBuilder::PARAM_INT);
        $result = $qb->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function getByGid($gid)
    {
        $queryBuilder = $this->conn->getQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('gid = :gid');
        $queryBuilder->setParameter('gid', $gid, IQueryBuilder::PARAM_STR);
        $result = $queryBuilder->executeQuery();
        return $result->fetchAssociative();
    }

    public function save(array $keys, $values = array(), $conditions = array())
    {
        return $this->conn->setValues($this->table, $keys, $values, $conditions);
    }

    public function deleteByGid($gid)
    {
        $qb = $this->conn->getQueryBuilder()
            ->delete($this->table)
            ->where('gid = :gid');
        $qb->setParameter('gid', $gid, IQueryBuilder::PARAM_STR);
        return $qb->executeStatement();
    }
    public function executeUpdate($sql, $values)
    {
        return $this->conn->executeUpdate($sql, $values);
    }

    public function updateStatus($gid, $status = 1)
    {
        $query = $this->conn->getQueryBuilder();
        $query->update($this->table)
            ->set("status", $query->createNamedParameter($status))
            ->where('gid = :gid');
        $query->setParameter('gid', $gid, IQueryBuilder::PARAM_STR);
        return $query->executeStatement();
        //$sql = sprintf("UPDATE %s set status = ? WHERE gid = ?", $this->prefixedTable);
        //$this->execute($sql, [$status, $gid]);
    }

    public function updateFilename($gid, $filename)
    {
        $query = $this->conn->getQueryBuilder();
        $query->update($this->table)
            ->set("filename", $query->createNamedParameter($filename))
            ->where('gid = :gid')
            ->andWhere('filename = :filename');
        $query->setParameter('gid', $gid, IQueryBuilder::PARAM_STR);
        $query->setParameter('filename', 'unknown', IQueryBuilder::PARAM_STR);
        return $query->executeStatement();
    }

    public function getDBType(): string
    {
        return \OC::$server->get(\OCP\IConfig::class)->getSystemValue('dbtype', "mysql");
    }

    public function getExtra($data)
    {
        if ($this->getDBType() == "pgsql" && is_resource($data)) {
            if (function_exists("pg_unescape_bytea")) {
                $extra = pg_unescape_bytea(stream_get_contents($data));
            }
            else {
                $extra = stream_get_contents($data);
            }
            return unserialize($extra);
        }
        return unserialize($data);
    }

}
