<?php

namespace App\Service;
use OpenAI;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class OpenAIService {

    public function __construct(
        private ContainerBagInterface $params
    ) {}

    /**
     * Returns a filename of a generated image
     * 
     * Responses are stored under public/generated/images
     * 
     * @param string $prompt
     * @return string Filename
     */
    public function generateImage(string $prompt): string {
        $client = OpenAI::client($this->params->get('app.openai.key'));
        $response = $client->images()->create([
            'prompt' => $prompt,
            'n' => 1,
            'size' => '256x256',
            'response_format' => 'b64_json'
        ]);

        $filename = md5($response->data[0]->b64_json) . ".jpg";
        $generatedDir = "generated/images";
        $dir = Path::normalize($this->params->get('kernel.project_dir') . "/public/" . $generatedDir);
        
        $filesystem = new Filesystem();

        try {
            if (!$filesystem->exists($dir)) {
                $filesystem->mkdir($dir);
            }
            $filesystem->dumpFile($dir . "/" . $filename, base64_decode($response->data[0]->b64_json));
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }
        return $generatedDir . "/" . $filename;
    }
    
    /**
     * Returns a filename containing an AI Generated response
     * 
     * Responses are stored under public/generated/text
     * 
     * @param string $prompt
     * @return string Filename
     */
    public function generateText(string $prompt): string {
        $client = OpenAI::client($this->params->get('app.openai.key'));
        $response = $client->chat()->create([
            'model' => $this->params->get('app.openai.model'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        $filename = $response->id . ".txt";
        $generatedDir = "generated/text";
        $dir = Path::normalize($this->params->get('kernel.project_dir') . "/public/" . $generatedDir);
        
        $filesystem = new Filesystem();

        try {
            if (!$filesystem->exists($dir)) {
                $filesystem->mkdir($dir);
            }
            $filesystem->dumpFile($dir . "/" . $filename, $response->choices[0]->message->content);
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }
        return $generatedDir . "/" . $filename;
    }

    /**
     * Returns a filename containing an AI Generated response
     * 
     * Responses are stored under public/generated/text
     * 
     * @param string[] $prompts Array of prompts
     * @return string Filename
     */
    public function generateTextFromMultiplePrompts(array $prompts): string {

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => "Pretend that you are a simple webserver that returns only valid HTML"];
        foreach ($prompts as $prompt) {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        $client = OpenAI::client($this->params->get('app.openai.key'));
        $response = $client->chat()->create([
            'model' => $this->params->get('app.openai.model'),
            'messages' => $messages            
        ]);

        $filename = $response->id . ".txt";
        $generatedDir = "generated/text";
        $dir = Path::normalize($this->params->get('kernel.project_dir') . "/public/" . $generatedDir);
        
        $filesystem = new Filesystem();

        try {
            if (!$filesystem->exists($dir)) {
                $filesystem->mkdir($dir);
            }
            $filesystem->dumpFile($dir . "/" . $filename, $response->choices[0]->message->content);
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }
        return $generatedDir . "/" . $filename;
    }

    /**
     * Extracts HTML from AI response
     * 
     * @param string $filename
     * @return string HTML
     */
    public function extractHtmlFromResponse(string $filename): string {

        $result = "";
        
        $isHtml = false;
        foreach (file($filename) as $line) {
            if (preg_match("/```html$/", $line)) {
                $isHtml = true;
                continue;
            }
            if (preg_match("/```$/", $line)) {
                $isHtml = false;
            }
            if ($isHtml) {
                $result .= $line . "\n";
            }
        };

        return $result;
    }

}