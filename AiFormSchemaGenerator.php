<?php declare(strict_types = 1);

namespace App\Model\Utils;

use Nette\Forms\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\RadioList;
use Nette\Utils\Json;

class AiFormSchemaGenerator
{
    public function generateSchema(Form $form): string
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        foreach ($form->getComponents() as $name => $control) {
            if ($control instanceof BaseControl) {
                $property = $this->getPropertyForControl($control);
                $schema['properties'][$name] = $property;

                if ($control->isRequired()) {
                    $schema['required'][] = $name;
                }
            }
        }

        return Json::encode($schema);
    }

    private function getPropertyForControl(BaseControl $control): array
    {
        $property = [
            'type' => $this->getPropertyType($control),
            'title' => $control->caption,
        ];

        if ($control instanceof SelectBox || $control instanceof RadioList) {
            $property['enum'] = array_keys($control->getItems());
        }

        if ($control instanceof MultiSelectBox) {
            $property['type'] = 'array';
            $property['items'] = [
                'type' => 'string',
                'enum' => array_keys($control->getItems()),
            ];
        }

        return $property;
    }

    private function getPropertyType(BaseControl $control): string
    {
        if ($control instanceof TextInput) {
            return 'string';
        }
        if ($control instanceof TextArea) {
            return 'string';
        }
        if ($control instanceof SelectBox) {
            return 'string';
        }
        if ($control instanceof Checkbox) {
            return 'boolean';
        }
        if ($control instanceof RadioList) {
            return 'string';
        }

        // Default to string if type is not recognized
        return 'string';
    }
}