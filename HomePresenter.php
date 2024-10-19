<?php declare(strict_types = 1);

namespace App\UI\Modules\Front\Home;

use App\UI\Modules\Front\BaseFrontPresenter;
use Nette\Application\UI\Form;
use App\UI\Form\AiForm;
use OpenAI\OpenAI;

final class HomePresenter extends BaseFrontPresenter
{

    private array $items = [5,4,8,9,7];
    public function actionDefault(): void
    {
        $this->template->title = 'AI form testing';
    }

    public function createComponentLlmForm(): Form
    {
        $form = new AiForm();
        $form->addTextAi('name', 'Jméno', 'Celé jméno osoby')->setAttribute('data-llm-description', 'Jméno osoby');
        $form->addEmailAi('email', 'E-mail', 'Kontaktní e-mailová adresa')->setAttribute('data-llm-description', 'E-mail osoby');
        $form->addIntegerAi('age', 'Věk', 'Věk osoby v letech')->setAttribute('data-llm-description', 'Věk osoby v letech');
        $form->addSelectAi('gender', 'Pohlaví', [
            'm' => 'Muž',
            'f' => 'Žena',
            'o' => 'Jiné'
        ], 'Pohlaví osoby')->setAttribute('data-llm-description', 'Pohlaví osoby');
        $form->addTextAreaAi('bio', 'O mně', 'Krátký popis osoby')->setAttribute('data-llm-description', 'Krátký popis osoby');
        $form->addCheckboxAi('newsletter', 'Odebírat newsletter', 'Souhlas s odběrem newsletteru')->setAttribute('data-llm-description', 'Souhlas s odběrem newsletteru');
        $form->addDateAi('birthdate', 'Datum narození', 'Datum narození ve formátu RRRR-MM-DD')->setAttribute('data-llm-description', 'Datum narození ve formátu RRRR-MM-DD');
        $form->addTextAi('address', 'Adresa')->setAttribute('data-llm-description', 'Adresa');
        $form->addTextAi('city', 'Město')->setAttribute('data-llm-description', 'Město');
        $form->addTextAi('ico', 'IČO')->setAttribute('data-llm-description', 'IČO');
        $form->addTextAi('dic', 'DIC')->setAttribute('data-llm-description', 'DIC');


        $form->addTextArea('prompt', 'Prompt pro AI');
        $form->addSubmit('aiAutofill', 'Vygenerovat pomocí AI')
        ->onClick[] = [$this, 'handleAiAutofill'];

        $form->addSubmit('submit', 'Save');
        return $form;
    }
    public function handleAiAutofill()
    {
        $clipboardText = $this->getHttpRequest()->getPost('clipboardText');
        $form = $this['llmForm'];
    
        if(empty($clipboardText)) {
            $clipboardText = $form->getValues()['prompt'];
        }

        $prompt = $form->generateAiPrompt(clipboard: $clipboardText);
        bdump($prompt);

        // Call OpenAI API
        //$openAiResponse = $this->callOpenAiApi($prompt);

        $openAiResponse = json_decode($this->callOpenAiApiNative($prompt), true );

        bdump($openAiResponse);
        $form->setDefaults($openAiResponse);
        $this->redrawControl('llmForm');
        $this->flashMessage('Form was successfully autofilfilled', 'success');
        $this->sendJson(['status' => "success", 'data' => $openAiResponse]);
    }

    // private function callOpenAiApi($prompt)
    // {

    //     $yourApiKey = '...';
    //     $client = \OpenAI::client($yourApiKey);
    //     $result = $client->chat()->create([
    //         'model' => 'gpt-4o',
    //         'messages' => [
    //             ['role' => 'user', 'content' => $prompt],
    //         ],
    //     ]);
    //     return $result->choices[0]->message->content ?? '';
    // }

    private function callOpenAiApiNative($prompt): string
    {
        $apiKey = '...';
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o-mini',
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? '';
        } else {
            // Handle error
            return 'Error: ' . $httpCode . ' - ' . $response;
        }
    }
    
}
