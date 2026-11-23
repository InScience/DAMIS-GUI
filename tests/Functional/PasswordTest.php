<?php

namespace App\Tests\Functional;

class PasswordTest extends DamisWebTestCase
{
    /** "Forgot password" sends the reset link by e-mail, and the new password set through it works. */
    public function testPasswordResetByEmail(): void
    {
        $user = $this->createUser('reset');

        $crawler = $this->client->request('GET', '/resetting/request');
        $this->client->submit($crawler->filter('form.fos_user_resetting_request')->form(['username' => $user->getEmail()]));

        self::assertResponseRedirects();
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'To', $user->getEmail());
        self::assertSame(1, preg_match('#/resetting/reset/([A-Za-z0-9_\-]+)#', $email->getTextBody(), $match), 'reset link in the e-mail');

        $crawler = $this->client->request('GET', '/resetting/reset/'.$match[1]);
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form.fos_user_resetting_reset')->form();
        $this->setPasswordFields($form, 'New-password-2');
        $this->client->submit($form);
        self::assertResponseRedirects();

        $this->client->request('GET', '/logout');
        $this->loginWithForm($user->getUsername(), 'New-password-2');
        self::assertResponseRedirects('http://localhost/');
    }

    public function testChangePassword(): void
    {
        $user = $this->createUser('change');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/profile/change-password');
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form.fos_user_change_password')->form();
        foreach ($form->all() as $name => $field) {
            if (str_ends_with($name, '[current_password]')) {
                $field->setValue('Test-password-1');
            }
        }
        $this->setPasswordFields($form, 'Changed-password-3');
        $this->client->submit($form);
        self::assertResponseRedirects();

        $this->client->request('GET', '/logout');
        $this->loginWithForm($user->getUsername(), 'Changed-password-3');
        self::assertResponseRedirects('http://localhost/');
    }

    private function setPasswordFields(\Symfony\Component\DomCrawler\Form $form, string $password): void
    {
        $set = 0;
        foreach ($form->all() as $name => $field) {
            if (str_contains($name, '[plainPassword]')) {
                $field->setValue($password);
                ++$set;
            }
        }
        self::assertSame(2, $set, 'new password and its repetition');
    }
}
