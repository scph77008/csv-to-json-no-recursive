<?php


class CsvConverter
{
    const ID_FIELD = 0;
    const PARENT_ID_FIELD = 1;
    const NAME_FIELD = 2;

    private $_data;
    private $_parents;

    public function __construct(string $filename)
    {
        $data = $parents = [];
        $resource = fopen($filename, 'r');

        while (($row = fgetcsv($resource, 1000, ",")) !== false) {

            // Пропускаем "шапку"
            if(!is_numeric($row[self::ID_FIELD])) {
                continue;
            }

            $parents[$row[self::ID_FIELD]] = $row[self::PARENT_ID_FIELD];

            $data[$row[self::ID_FIELD]] = [
                'id' => $row[self::ID_FIELD],
                'parent_id' => $row[self::PARENT_ID_FIELD],
                'name' => $row[self::NAME_FIELD]
            ];
        }
        fclose($resource);

        $this->_data = $data;
        $this->_parents = $parents;
    }

    private function findParent(int $childId): int
    {
        return (int)$this->_parents[$childId];
    }

    public function asJson(): string
    {
        $output = [];
        foreach ($this->_data as $row) {

            $path = [];
            $parentId = $row['id'];

            do {
                $parentId = $this->findParent($parentId);
                if ($parentId !== 0) {
                    $path[] = $parentId;
                }
            } while ($parentId !== 0);

            $item = &$output;
            foreach (array_reverse($path) as $step) {
                $item = &$item[$step];

                if (!array_key_exists('children', $item))
                    $item['children'] = [];

                $item = &$item['children'];
            }

            $item[$row['id']] = ['name' => $row['name']];
        }
        unset($item);
        return json_encode($output);
    }
}

$tree = new CsvConverter('data.csv');
echo $tree->asJson();