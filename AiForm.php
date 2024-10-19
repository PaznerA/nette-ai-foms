<?php

namespace App\UI\Form;

use App\Model\Utils\AiFormSchemaGenerator;
use Nette\Application\UI\Form;
use Nette\Utils\Json;

class AiForm extends Form
{
    /** @var array */
    private $aiData = [];

    public function __construct()
    {
        parent::__construct();
        $this->addContainer('AiClipboardFillContainer');
        $this->addTextArea('aiClipboardFill', 'AI Clipboard Fill')
            ->setHtmlAttribute('id', 'aiClipboardFill');
    }

    public function getJsonSchema(): false|string
    {
        $schema = [];
        foreach ($this->getControls() as $control) {
            $schema[$control->getName()] = [
                'label' => $control->caption,
                'llmDescription' => $control->llmDescription,
                'type' => get_class($control),
            ];
        }
        return json_encode($schema);
    }

    

    public function aiClipboardFillSubmitClicked()
    {
        $text = $this->getComponent('aiClipboardFill')->getValue();
        if($text && strlen($text) > 20) {
            bdump($text);
            $data = $this->callLlm($text);
            $this->fillFormFromAiResponse($data);
        }
    }

    public function addTextAi(string $name, ?string $label = null, ?string $aiDescription = null)
    {
        $control = parent::addText($name, $label);
        $this->aiData[$name] = [
            'type' => 'text',
            'label' => $label,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addTextAreaAi(string $name, ?string $label = null, ?string $aiDescription = null)
    {
        $control = parent::addTextArea($name, $label);
        $this->aiData[$name] = [
            'type' => 'textarea',
            'label' => $label,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addEmailAi(string $name, ?string $label = null, ?string $aiDescription = null)
    {
        $control = parent::addEmail($name, $label);
        $this->aiData[$name] = [
            'type' => 'email',
            'label' => $label,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addIntegerAi(string $name, ?string $label = null, ?string $aiDescription = null)
    {
        $control = parent::addInteger($name, $label);
        $this->aiData[$name] = [
            'type' => 'integer',
            'label' => $label,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addSelectAi(string $name, ?string $label = null, array $items = null, ?string $aiDescription = null)
    {
        $control = parent::addSelect($name, $label, $items);
        $this->aiData[$name] = [
            'type' => 'select',
            'label' => $label,
            'items' => $items,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addMultiSelectAi(string $name, ?string $label = null, array $items = null, ?string $aiDescription = null)
    {
        $control = parent::addMultiSelect($name, $label, $items);
        $this->aiData[$name] = [
            'type' => 'multiselect',
            'label' => $label,
            'items' => $items,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addCheckboxAi(string $name, ?string $caption = null, ?string $aiDescription = null)
    {
        $control = parent::addCheckbox($name, $caption);
        $this->aiData[$name] = [
            'type' => 'checkbox',
            'caption' => $caption,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addRadioListAi(string $name, ?string $label = null, array $items = null, ?string $aiDescription = null)
    {
        $control = parent::addRadioList($name, $label, $items);
        $this->aiData[$name] = [
            'type' => 'radiolist',
            'label' => $label,
            'items' => $items,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    public function addDateAi(string $name, ?string $label = null, ?string $aiDescription = null)
    {
        $control = parent::addDate($name, $label);
        $this->aiData[$name] = [
            'type' => 'date',
            'label' => $label,
            'aiDescription' => $aiDescription,
        ];
        return $control;
    }

    /**
     * Generuje prompt pro LLM.
     * @param string $clipboard
     * @return string
     */
    public function generateAiPrompt(string $clipboard): string
    {
        $formStructure = $this->getExtendedFormStructure();

        return "
        Form schema: {$formStructure}
        \n\n
        User content: {$clipboard}
        \n\n
        -----
        Return me valid JSON that will represent the user content expressed in the attached schema, exclude empty fields that you don't recognize, for enums return always the value from the array in schema.
        ";
    }

    /**
     * Vyplní formulář na základě odpovědi AI.
     * @param string $response
     */
    private function fillFormFromAiResponse(string $response)
    {
        $data = Json::decode($response, Json::FORCE_ARRAY);
        foreach ($data as $name => $value) {
            if ($this->offsetExists($name)) {
                $this[$name]->setValue($value);
            }
        }
    }
    private function getExtendedFormStructure(): string
    {
        $schemaGenerator = new AiFormSchemaGenerator();
        return $schemaGenerator->generateSchema($this);
    }

    public function render(...$args): void
	{
        $formStructure = $this->getExtendedFormStructure();

		$this->fireRenderEvents();
		parent::render(...$args);
	}
}
