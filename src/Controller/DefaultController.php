<?php

namespace App\Controller;

use App\Service\OpenAIService;
use OpenAI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(OpenAIService $openAIService, CacheInterface $cacheInterface): Response
    {

        $background_image = $cacheInterface->get("bg:default", function(ItemInterface $item) use ($openAIService) {
            // $item->expiresAfter(3600);
            return $openAIService->generateImage("A 90s style web background");
        });

        $content_image_url = $cacheInterface->get("content_image:default", function(ItemInterface $item) use ($openAIService) {            
            // $item->expiresAfter(3600);
            return $openAIService->generateImage("A 90's computer hacker staring into a green screen terminal");
        });

        $body_text = $cacheInterface->get("text:default", function(ItemInterface $item) use ($openAIService) {            
            // $item->expiresAfter(3600);
            return $openAIService->generateText("Can you describe the movie Hackers as if you were a teenager in the 90's");
        });

        $menu_items = $cacheInterface->get("text:menu", function(ItemInterface $item) use ($openAIService) {            
            // $item->expiresAfter(3600);
            return $openAIService->generateTextFromMultiplePrompts([
                "Can I get html of an unordered list containing containing links to pages you'd find on a webpage about a movie, each href should just contain '#'"
            ]);
        });

        $menu_item_text = $openAIService->extractHtmlFromResponse($menu_items); 


        return $this->render('default/index.html.twig', [
            'background_url' => $background_image,
            'content_image_url' => $content_image_url,
            'menu_items' => $menu_item_text,
            'body_text' => file_get_contents($body_text)
        ]);
    }
}
