<?php

namespace CaTools\Profile;
use PDO;
use Phinx\Migration\AbstractMigration;

abstract class AbstractCreateSQLFunctionMigration extends AbstractMigration
{

    /**
     * Executes sql after stripping invalid 'DELIMITER' clauses as these are client only. Runs each statement individually.
     * @param $sql string SQL to execute.
     */
    public function execute(string $sql, array $params = []): int
    {
        /** @var PDO $pdo */
        $pdo = $this->getAdapter()->getConnection();
        $pattern = '/delimiter ([\w;.!?\\-\/]+)/i';
        if (preg_match($pattern, $sql, $matches)) {
            $sql = preg_replace($pattern, '', $sql);
            $split_pattern = '/' . preg_quote($matches[1], '/') . '/';
            $sql = preg_split($split_pattern, $sql);
        }
        if (!is_array($sql)) {
            $sql = [$sql];
        }
        $status = false;
        foreach ($sql as $query) {
            $query = trim($query);
            if ($query) {
                $stmt = $pdo->prepare($query);
                $status |= $stmt->execute();
            }
        }
        return $status;
    }
}
