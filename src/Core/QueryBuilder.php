<?php

namespace Core;

/**
 * Fluent SQL Query Builder
 */
class QueryBuilder
{
  protected Database $db;
  protected string $table;
  protected string $select = '*';
  protected array $wheres = [];
  protected array $bindings = [];
  protected ?string $orderBy = null;
  protected ?int $limit = null;
  protected ?int $offset = null;
  protected array $joins = [];
  protected ?string $groupBy = null;
  protected array $having = [];

  public function __construct(Database $db, string $table)
  {
    $this->db = $db;
    $this->table = $table;
  }

  public function select(string|array $columns = '*'): self
  {
    $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
    return $this;
  }

  public function where(string $column, mixed $operator = null, mixed $value = null): self
  {
    if (func_num_args() === 2) {
      $value = $operator;
      $operator = '=';
    }

    $this->wheres[] = [
      'column' => $column,
      'operator' => $operator,
      'value' => $value,
      'boolean' => 'AND'
    ];

    $this->bindings[] = $value;
    return $this;
  }

  public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
  {
    if (func_num_args() === 2) {
      $value = $operator;
      $operator = '=';
    }

    $this->wheres[] = [
      'column' => $column,
      'operator' => $operator,
      'value' => $value,
      'boolean' => 'OR'
    ];

    $this->bindings[] = $value;
    return $this;
  }

  public function whereNull(string $column): self
  {
    $this->wheres[] = [
      'column' => $column,
      'operator' => 'IS NULL',
      'value' => null,
      'boolean' => 'AND'
    ];
    return $this;
  }

  public function whereNotNull(string $column): self
  {
    $this->wheres[] = [
      'column' => $column,
      'operator' => 'IS NOT NULL',
      'value' => null,
      'boolean' => 'AND'
    ];
    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->orderBy = "{$column} {$direction}";
    return $this;
  }

  public function limit(int $limit): self
  {
    $this->limit = $limit;
    return $this;
  }

  public function offset(int $offset): self
  {
    $this->offset = $offset;
    return $this;
  }

  public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
  {
    $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
    return $this;
  }

  public function leftJoin(string $table, string $first, string $operator, string $second): self
  {
    return $this->join($table, $first, $operator, $second, 'LEFT');
  }

  public function rightJoin(string $table, string $first, string $operator, string $second): self
  {
    return $this->join($table, $first, $operator, $second, 'RIGHT');
  }

  public function groupBy(string $column): self
  {
    $this->groupBy = $column;
    return $this;
  }

  public function having(string $column, string $operator, mixed $value): self
  {
    $this->having[] = "{$column} {$operator} ?";
    $this->bindings[] = $value;
    return $this;
  }

  protected function buildQuery(): string
  {
    $sql = "SELECT {$this->select} FROM {$this->table}";

    if (!empty($this->joins)) {
      $sql .= ' ' . implode(' ', $this->joins);
    }

    if (!empty($this->wheres)) {
      $sql .= ' WHERE ';
      $first = true;

      foreach ($this->wheres as $where) {
        if (!$first) {
          $sql .= " {$where['boolean']} ";
        }

        if (in_array($where['operator'], ['IS NULL', 'IS NOT NULL'])) {
          $sql .= "{$where['column']} {$where['operator']}";
        } else {
          $sql .= "{$where['column']} {$where['operator']} ?";
        }

        $first = false;
      }
    }

    if ($this->groupBy) {
      $sql .= " GROUP BY {$this->groupBy}";
    }

    if (!empty($this->having)) {
      $sql .= ' HAVING ' . implode(' AND ', $this->having);
    }

    if ($this->orderBy) {
      $sql .= " ORDER BY {$this->orderBy}";
    }

    if ($this->limit) {
      $sql .= " LIMIT {$this->limit}";
    }

    if ($this->offset) {
      $sql .= " OFFSET {$this->offset}";
    }

    return $sql;
  }

  public function get(): array
  {
    $sql = $this->buildQuery();
    $stmt = $this->db->prepare($sql);
    $stmt->execute($this->bindings);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function first(): ?array
  {
    $this->limit(1);
    $results = $this->get();
    return $results[0] ?? null;
  }

  public function count(): int
  {
    $originalSelect = $this->select;
    $this->select = 'COUNT(*) as count';

    $result = $this->first();
    $this->select = $originalSelect;

    return (int) ($result['count'] ?? 0);
  }

  public function paginate(int $perPage = 10, int $currentPage = 1): array
  {
    $total = $this->count();
    $this->limit($perPage)->offset(($currentPage - 1) * $perPage);

    $items = $this->get();

    return [
      'items' => $items,
      'total' => $total,
      'per_page' => $perPage,
      'current_page' => $currentPage,
      'last_page' => (int) ceil($total / $perPage)
    ];
  }

  public function insert(array $data): int
  {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(array_values($data));

    return $this->db->lastInsertId();
  }

  public function update(array $data): int
  {
    $sets = [];
    $values = [];

    foreach ($data as $column => $value) {
      $sets[] = "{$column} = ?";
      $values[] = $value;
    }

    $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

    if (!empty($this->wheres)) {
      $sql .= ' WHERE ';
      $first = true;

      foreach ($this->wheres as $where) {
        if (!$first) {
          $sql .= " {$where['boolean']} ";
        }

        if ($where['value'] !== null) {
          $values[] = $where['value'];
        }

        if (in_array($where['operator'], ['IS NULL', 'IS NOT NULL'])) {
          $sql .= "{$where['column']} {$where['operator']}";
        } else {
          $sql .= "{$where['column']} {$where['operator']} ?";
        }

        $first = false;
      }
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($values);

    return $stmt->rowCount();
  }

  public function delete(): int
  {
    $sql = "DELETE FROM {$this->table}";

    if (!empty($this->wheres)) {
      $sql .= ' WHERE ';
      $first = true;

      foreach ($this->wheres as $where) {
        if (!$first) {
          $sql .= " {$where['boolean']} ";
        }

        if (in_array($where['operator'], ['IS NULL', 'IS NOT NULL'])) {
          $sql .= "{$where['column']} {$where['operator']}";
        } else {
          $sql .= "{$where['column']} {$where['operator']} ?";
        }

        $first = false;
      }
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($this->bindings);

    return $stmt->rowCount();
  }

  public function __call(string $method, array $parameters)
  {
    if (method_exists($this->db, $method)) {
      return call_user_func_array([$this->db, $method], $parameters);
    }

    throw new \BadMethodCallException("Method {$method} does not exist.");
  }
}
