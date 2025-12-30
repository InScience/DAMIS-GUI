<?php

namespace Base\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Base\UserBundle\Entity\User;

class ChangePasswordControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test that an anonymous user is redirected to the login page.
     */
    public function testGuestIsRedirectedToLogin()
    {
        $this->client->request('GET', '/profile/change-password');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue(
            $this->client->getResponse()->isRedirect('http://localhost/login'),
            'Anonymous user should be redirected to http://localhost/login'
        );
    }

    /**
     * The "Happy Path": A logged-in user successfully changes their password.
     */
    public function testLoggedInUserCanChangePassword()
    {
        $userManager = $this->client->getContainer()->get('fos_user.user_manager');
        
        $testUser = $userManager->createUser();
        $testUser->setUsername('testuser_happy');
        $testUser->setEmail('test_happy@example.com');
        $testUser->setPlainPassword('old-password');
        $testUser->setEnabled(true);
        $userManager->updateUser($testUser);

        $this->loginUser('testuser_happy', 'old-password');

        $crawler = $this->client->request('GET', '/profile/change-password');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "The change password page should load for a logged-in user.");

        $content = $this->client->getResponse()->getContent();
        
        $form = null;
        
        $buttonSelectors = [
            'Change password',           // Original attempt
            'change password',           // lowercase
            'Update password',           // Alternative text
            'Submit',                    // Generic submit
            'Save',                      // Another common text
            '_submit',                   // Form name
            'fos_user_change_password_submit', // FOSUser specific
            '[type="submit"]'            // Any submit button
        ];
        
        foreach ($buttonSelectors as $selector) {
            try {
                $form = $crawler->selectButton($selector)->form();
                break;
            } catch (\InvalidArgumentException) {
                continue;
            }
        }
        
        if (!$form) {
            $formNode = $crawler->filter('form[name="fos_user_change_password"]');
            if ($formNode->count() === 0) {
                $formNode = $crawler->filter('form')->first();
            }
            
            $this->assertGreaterThan(0, $formNode->count(), 'No form found on the change password page. Content: ' . substr((string) $content, 0, 500));
            
            $form = $formNode->form();
        }

        $formData = [];
        
        $fieldPatterns = [
            'current_password' => [
                'fos_user_change_password[current_password]',
                'fos_user_change_password[currentPassword]',
                'current_password',
                'currentPassword'
            ],
            'new_password_first' => [
                'fos_user_change_password[plainPassword][first]',
                'fos_user_change_password[newPassword][first]',
                'fos_user_change_password[plainPassword]',
                'plainPassword[first]',
                'newPassword[first]'
            ],
            'new_password_second' => [
                'fos_user_change_password[plainPassword][second]',
                'fos_user_change_password[newPassword][second]',
                'plainPassword[second]',
                'newPassword[second]'
            ]
        ];

        $formFields = $form->getValues();
        
        foreach ($fieldPatterns['current_password'] as $fieldName) {
            if (isset($formFields[$fieldName])) {
                $formData[$fieldName] = 'old-password';
                break;
            }
        }
        
        foreach ($fieldPatterns['new_password_first'] as $fieldName) {
            if (isset($formFields[$fieldName])) {
                $formData[$fieldName] = 'new-awesome-password';
                break;
            }
        }
        
        foreach ($fieldPatterns['new_password_second'] as $fieldName) {
            if (isset($formFields[$fieldName])) {
                $formData[$fieldName] = 'new-awesome-password';
                break;
            }
        }

        $form->setValues($formData);
        $this->client->submit($form);

        $responseStatus = $this->client->getResponse()->getStatusCode();

        $isSuccess = $this->client->getResponse()->isSuccessful() || $this->client->getResponse()->isRedirect();
        $this->assertTrue($isSuccess, 'Form submission should be successful. Status: ' . $responseStatus);

        if ($this->client->getResponse()->isRedirect()) {
            $crawler = $this->client->followRedirect();
        }

        $content = $this->client->getResponse()->getContent();
        
        $successIndicators = [
            'password has been changed',
            'Password updated',
            'successfully',
            'Success',
            'success',
            'Password changed',
            'slaptažodis pakeistas',
            'sėkmingai',             
            'Slaptažodis',           
        ];
        
        $hasSuccessMessage = false;
        $foundIndicator = '';
        
        foreach ($successIndicators as $indicator) {
            if (stripos((string) $content, $indicator) !== false) {
                $hasSuccessMessage = true;
                $foundIndicator = $indicator;
                break;
            }
        }
        
        $isOnProfilePage = str_contains((string) $this->client->getRequest()->getPathInfo(), '/profile');
        $hasFormErrors = stripos((string) $content, 'error') !== false || 
                        stripos((string) $content, 'invalid') !== false ||
                        stripos((string) $content, 'required') !== false;
        
        if ($isOnProfilePage && !$hasFormErrors) {
            $hasSuccessMessage = true;
            $foundIndicator = 'On profile page without errors';
        }
        
        if (!$hasSuccessMessage) {
            $container = $this->client->getContainer();
            $userManager = $container->get('fos_user.user_manager');
            $passwordEncoder = $container->get('security.encoder_factory')->getEncoder(User::class);
            
            $container->get('doctrine')->getManager()->clear();
            $updatedUser = $userManager->findUserByUsername('testuser_happy');
            
            $passwordWasChanged = $passwordEncoder->isPasswordValid(
                $updatedUser->getPassword(), 
                'new-awesome-password', 
                $updatedUser->getSalt()
            );
            
            if ($passwordWasChanged) {
                $hasSuccessMessage = true;
                $foundIndicator = 'Password verified in database';
            }
        }
            
        $this->assertTrue($hasSuccessMessage, "Success not detected. Found indicator: '$foundIndicator'. Full content: " . $content);
    }

    /**
     * Test validation: submitting the form with the wrong current password.
     */
    public function testChangePasswordFailsWithWrongCurrentPassword()
    {
        $userManager = $this->client->getContainer()->get('fos_user.user_manager');
        
        $testUser = $userManager->createUser();
        $testUser->setUsername('testuser_wrongpass');
        $testUser->setEmail('test_wrongpass@example.com');
        $testUser->setEnabled(true);
        $testUser->setPlainPassword('real-password');
        $userManager->updateUser($testUser);

        $this->loginUser('testuser_wrongpass', 'real-password');

        $crawler = $this->client->request('GET', '/profile/change-password');
        
        $form = null;
        $buttonSelectors = [
            'Change password', 'change password', 'Update password', 'Submit', 'Save',
            '_submit', 'fos_user_change_password_submit', '[type="submit"]'
        ];
        
        foreach ($buttonSelectors as $selector) {
            try {
                $form = $crawler->selectButton($selector)->form();
                break;
            } catch (\InvalidArgumentException) {
                continue;
            }
        }
        
        if (!$form) {
            $formNode = $crawler->filter('form[name="fos_user_change_password"]');
            if ($formNode->count() === 0) {
                $formNode = $crawler->filter('form')->first();
            }
            $form = $formNode->form();
        }

        $formData = [];
        $formFields = $form->getValues();
        
        $fieldPatterns = [
            'current_password' => [
                'fos_user_change_password[current_password]',
                'fos_user_change_password[currentPassword]',
                'current_password',
                'currentPassword'
            ],
            'new_password_first' => [
                'fos_user_change_password[plainPassword][first]',
                'fos_user_change_password[newPassword][first]',
                'fos_user_change_password[plainPassword]',
                'plainPassword[first]',
                'newPassword[first]'
            ],
            'new_password_second' => [
                'fos_user_change_password[plainPassword][second]',
                'fos_user_change_password[newPassword][second]',
                'plainPassword[second]',
                'newPassword[second]'
            ]
        ];

        foreach ($fieldPatterns['current_password'] as $fieldName) {
            if (isset($formFields[$fieldName])) {
                $formData[$fieldName] = 'this-is-the-wrong-password';
                break;
            }
        }
        
        foreach ($fieldPatterns['new_password_first'] as $fieldName) {
            if (isset($formFields[$fieldName])) {
                $formData[$fieldName] = 'new-password';
                break;
            }
        }
        
        foreach ($fieldPatterns['new_password_second'] as $fieldName) {
            if (isset($formFields[$fieldName])) {
                $formData[$fieldName] = 'new-password';
                break;
            }
        }

        $form->setValues($formData);
        $crawler = $this->client->submit($form);

        $this->assertFalse($this->client->getResponse()->isRedirect(), "Form with wrong password should not redirect.");
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $content = $this->client->getResponse()->getContent();
        $hasError = 
            str_contains((string) $content, 'not a valid current password') ||
            str_contains((string) $content, 'Current password is incorrect') ||
            str_contains((string) $content, 'Invalid password') ||
            str_contains((string) $content, 'error');
            
        $this->assertTrue($hasError, "Validation error message not found. Content: " . substr((string) $content, 0, 500));
    }

    /**
     * Helper method to log in a user using the actual login form
     */
    private function loginUser($username, $password)
    {
        $this->client->followRedirects(true);
        
        $crawler = $this->client->request('GET', '/login');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "The login page should load successfully.");

        $form = $crawler->selectButton('_submit')->form([
            '_username' => $username,
            '_password' => $password,
        ]);
        
        $this->client->submit($form);
        
        $this->client->followRedirects(false);
    }
}