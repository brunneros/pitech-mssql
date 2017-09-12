<?php

namespace Pitech\MssqlBundle\DBAL\Platforms;

use Doctrine\DBAL\Platforms\SQLServer2005Platform;
use Doctrine\DBAL\Platforms\SQLServer2008Platform;

/**
 * The DblibPlatform provides the behavior, features and SQL dialect of the MsSQL database platform.
 */
class DblibPlatform extends SQLServer2005Platform
{
    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'NVARCHAR(MAX)';
    }

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset = null)
    {
        if ($limit === null) {
            return $query;
        }

        $start   = $offset + 1;
        $end     = $offset + $limit;

        // We'll find a SELECT or SELECT distinct and prepend TOP n to it
        // Even if the TOP n is very large, the use of a CTE will
        // allow the SQL Server query planner to optimize it so it doesn't
        // actually scan the entire range covered by the TOP clause.
        $selectPattern = '/^(\s*SELECT\s+(?:DISTINCT\s+)?)(.*)$/i';
        $replacePattern = sprintf('$1%s $2', "TOP $end");
        $query = preg_replace($selectPattern, $replacePattern, $query);

        if (stristr($query, "ORDER BY")) {
//             Inner order by is not valid in SQL Server for our purposes
//             unless it's in a TOP N subquery.
            $query = $this->scrubInnerOrderBy($query);
        }

        // Build a new limited query around the original, using a CTE
        return sprintf(
            "WITH dctrn_cte AS (%s) "
            . "SELECT * FROM ("
            . "SELECT *, ROW_NUMBER() OVER (ORDER BY (SELECT 0)) AS doctrine_rownum FROM dctrn_cte"
            . ") AS doctrine_tbl "
            . "WHERE doctrine_rownum BETWEEN %d AND %d ORDER BY doctrine_rownum ASC",
            $query,
            $start,
            $end
        );
    }

    /**
     * Remove ORDER BY clauses in subqueries - they're not supported by SQL Server.
     * Caveat: will leave ORDER BY in TOP N subqueries.
     *
     * @param $query
     * @return string
     */
    private function scrubInnerOrderBy($query)
    {
        $count = substr_count(strtoupper($query), "ORDER BY");
        $offset = 0;

        while ($count-- > 0) {
            $qLen = strlen($query);
            $orderByPos = stripos($query, " ORDER BY", $offset);
            $parenCount = 0;
            $currentPosition = $orderByPos;

            while ($parenCount >= 0 && $currentPosition < $qLen) {

                if (!is_int($currentPosition)) {
                    break(2);
                }

                if ($query[$currentPosition] === '(') {
                    $parenCount++;
                } elseif ($query[$currentPosition] === ')') {
                    $parenCount--;
                }

                $currentPosition++;
            }

            if ($this->isOrderByInTopNSubquery($query, $orderByPos)) {
                // If the order by clause is in a TOP N subquery, do not remove
                // it and continue iteration from the current position.
                $offset = $currentPosition;
                continue;
            }

            if ($currentPosition < $qLen - 1) {
                $query = substr($query, 0, $orderByPos) . substr($query, $currentPosition - 1);
                $offset = $orderByPos;
            }
        }
        return $query;
    }

    /**
     * Check an ORDER BY clause to see if it is in a TOP N query or subquery.
     *
     * @param string $query The query
     * @param int $currentPosition Start position of ORDER BY clause
     * @return bool true if ORDER BY is in a TOP N query, false otherwise
     */
    private function isOrderByInTopNSubquery($query, $currentPosition)
    {
        // Grab query text on the same nesting level as the ORDER BY clause we're examining.
        $subQueryBuffer = '';
        $parenCount = 0;

        // If $parenCount goes negative, we've exited the subquery we're examining.
        // If $currentPosition goes negative, we've reached the beginning of the query.
        while ($parenCount >= 0 && $currentPosition >= 0) {
            if ($query[$currentPosition] === '(') {
                $parenCount--;
            } elseif ($query[$currentPosition] === ')') {
                $parenCount++;
            }

            // Only yank query text on the same nesting level as the ORDER BY clause.
            $subQueryBuffer = ($parenCount === 0 ? $query[$currentPosition] : " ") . $subQueryBuffer;

            $currentPosition--;
        }

        if (preg_match('/SELECT\s+(DISTINCT\s+)?TOP\s/i', $subQueryBuffer)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatString()
    {
        return $this->getDateTimeFormatByPhpVersion(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatString()
    {
        return $this->getDateTimeFormatByPhpVersion(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatString()
    {
        return $this->getDateTimeFormatByPhpVersion(__FUNCTION__);
    }

    /**
     * Return the correct datetime format depending on php version.
     * @param string $function - the function name to be called on parent if php version is old.
     * @return string - the date/datetime format
     */
    public function getDateTimeFormatByPhpVersion($function)
    {
        if (substr(phpversion(), 0, 3) >= '5.6') {
            return 'Y-m-d H:i:s';
        }
        return parent::$function();
    }
}
