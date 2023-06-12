<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Omeka\Form\Element\ArrayTextarea;

class DataTextarea extends ArrayTextarea
{
    /**
     * @var array
     */
    protected $dataKeys = [];

    /**
     * @var array
     */
    protected $dataArrayKeys = [];

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (array_key_exists('data_keys', $this->options)) {
            $this->setDataKeys($this->options['data_keys']);
        }
        if (array_key_exists('data_array_keys', $this->options)) {
            $this->setDataArrayKeys($this->options['data_array_keys']);
        }
        return $this;
    }

    public function arrayToString($array): string
    {
        if (is_string($array)) {
            return $array;
        }
        // Reorder values according to specified keys and fill empty values.
        $string = '';
        $countDataKeys = count($this->dataKeys);
        // Associative array.
        if ($countDataKeys) {
            $arrayKeys = array_intersect_key($this->dataArrayKeys, $this->dataKeys);
            foreach ($array as $values) {
                $data = array_replace($this->dataKeys, $values);
                // Manage sub-values.
                foreach ($arrayKeys as $arrayKey => $arraySeparator) {
                    $data[$arrayKey] = implode(' ' . $arraySeparator . ' ', array_map('strval', isset($data[$arrayKey]) ? (array) $data[$arrayKey] : []));
                }
                $string .= implode(' ' . $this->keyValueSeparator . ' ', array_map('strval', $data)) . "\n";
            }
        }
        // Simple list.
        else {
            foreach ($array as $values) {
                $data = array_values($values);
                $string .= implode(' ' . $this->keyValueSeparator . ' ', array_map('strval', $data)) . "\n";
            }
        }
        return $string;
    }

    public function stringToArray($string): array
    {
        if (is_array($string)) {
            return $string;
        }
        $array = [];
        $countDataKeys = count($this->dataKeys);
        if ($countDataKeys) {
            $arrayKeys = array_intersect_key($this->dataArrayKeys, $this->dataKeys);
            foreach ($this->stringToList($string) as $values) {
                // Set keys to each part of the line.
                $keys = array_keys($this->dataKeys);
                $values = array_map('trim', explode($this->keyValueSeparator, $values, $countDataKeys));
                // Add empty missing values. The number cannot be higher.
                // TODO Use substr_count() if quicker.
                $missing = $countDataKeys - count($values);
                if ($missing) {
                    $values = array_merge($values, array_fill(0, $missing, ''));
                }
                $data = array_combine($keys, $values);
                // Manage sub-values.
                foreach ($arrayKeys as $arrayKey => $arraySeparator) {
                    $data[$arrayKey] = $data[$arrayKey] === ''
                        ? []
                        : array_map('trim', explode($arraySeparator, $data[$arrayKey]));
                }
                $this->asKeyValue
                    ? $array[reset($data)] = $data
                    : $array[] = $data;
            }
        } else {
            foreach ($this->stringToList($string) as $values) {
                // No keys: a simple list.
                $data = array_map('trim', explode($this->keyValueSeparator, $values));
                $this->asKeyValue
                    ? $array[reset($data)] = $data
                    : $array[] = $data;
            }
        }
        return $array;
    }

    /**
     * Set the ordered list of keys to use for each line.
     *
     * Each specified key will be used as the keys of each part of each line.
     * There is no default keys: in that case, the values are a simple array of
     * array.
     * With option "as_key_value", the first value will be the used as key for
     * the main array too.
     */
    public function setDataKeys(array $dataKeys)
    {
        $this->dataKeys = array_fill_keys($dataKeys, null);
        return $this;
    }

    /**
     * Get the list of data keys.
     */
    public function getDataKeys(): array
    {
        return aray_keys($this->dataKeys);
    }

    /**
     * Set the option to separate values into multiple values.
     *
     * It should be a subset of the data keys.
     *
     * It is not recommended to set the first key when option "as_key_value" is
     *  set. In that case, the whole value is used as key before to be splitted.
     */
    public function setDataArrayKeys(array $dataArrayKeys)
    {
        $this->dataArrayKeys = $dataArrayKeys;
        return $this;
    }

    /**
     * Get the option to separate values into multiple values.
     */
    public function getDataArrayKeys(): array
    {
        return $this->dataArrayKeys;
    }
}
