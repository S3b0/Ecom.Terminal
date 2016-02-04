<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController;

class AuthenticationController extends AbstractAuthenticationController
{

    const MIN_USERNAME_LENGTH = 4;
    const MIN_PASSWORD_LENGTH = 8;

    /**
     * @var \TYPO3\Flow\Security\AccountFactory
     * @Flow\Inject
     */
    protected $accountFactory;

    /**
     * @var \TYPO3\Flow\Security\AccountRepository
     * @Flow\Inject
     */
    protected $accountRepository;

    /**
     * Will be triggered upon successful authentication
     *
     * @param ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there
     *                                       was none
     *
     * @return string
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null)
    {
        $this->addFlashMessage('Login successful');
        if ($originalRequest !== null) {
            $this->redirectToRequest($originalRequest);
        }
        $this->redirect('index', 'Appointment');
    }

    protected function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = null)
    {
        $this->addFlashMessage($exception->getMessage(), 'Authentication failed!', Message::SEVERITY_ERROR, [], $exception->getCode());
    }

    /**
     * Logs all active tokens out and redirects the user to the login form
     *
     * @return void
     */
    public function logoutAction()
    {
        parent::logoutAction();
        $this->addFlashMessage('Logout successful');
        $this->redirect('login');
    }

    /**
     * Displays a registration form
     *
     * @param string $identifier
     *
     * @return void
     */
    public function registerAction($identifier = '')
    {
        $this->view->assign('identifier', $identifier);
    }

    /**
     * @param string $identifier
     * @param string $password
     * @param string $passwordCheck
     * @param int    $role
     *
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function createAction($identifier, $password, $passwordCheck, $role = 0)
    {
        if ($identifier === '' || strlen($identifier) < self::MIN_USERNAME_LENGTH) {
            $this->addFlashMessage('Username must be at least ' . self::MIN_USERNAME_LENGTH . ' characters long.', 'Username too short', Message::SEVERITY_WARNING);
        } elseif (!preg_match('/^[a-z][a-z0-9-_]+$/i', $identifier)) {
            $this->addFlashMessage('Username must contain alphanumeric characters, hyphens and underscores only.', 'Invalid characters found in username', Message::SEVERITY_WARNING);
        } elseif ($this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($identifier, 'DefaultProvider') instanceof \TYPO3\Flow\Security\Account) {
            $this->addFlashMessage('Please choose another username.', 'User already exists', Message::SEVERITY_WARNING);
        } elseif ($errorMessage = $this->checkPassword($identifier, $password)) {
            $this->addFlashMessage($errorMessage, 'Password validation failed', Message::SEVERITY_WARNING);
        } elseif ($password !== $passwordCheck) {
            $this->addFlashMessage('Passwords do not match', 'Wrong password confirmation', Message::SEVERITY_WARNING);
        } else {
            switch ($role) {
                case 1:
                    $roles = ['Ecom.Terminal:Administrator'];
                    break;
                default:
                    $roles = ['Ecom.Terminal:User'];
            }
            // create an account with password and add it to the accountRepository
            $account = $this->accountFactory->createAccountWithPassword($identifier, $password, $roles);
            $this->accountRepository->add($account);
            // add a message and redirect to login form
            $this->addFlashMessage('Account created. Please login.');
            $this->redirect('login');
        }

        $this->redirect('register', null, null, [ 'identifier' => $identifier ]);
    }

    /**
     * @param $user
     * @param $password
     *
     * @return null|string
     */
    private function checkPassword($user, $password) {
        $word_file = '/usr/share/dict/words';

        $lowercasePassword = strtolower($password);
        // also check password with numbers or punctuation subbed for letters
        $leetspeakPassword = strtr($lowercasePassword, '5301!', 'seoll');
        $lc_user = strtolower($user);

        // the password must be at least six characters
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'The password is too short.';
        }

        // the password can't be the username (or reversed username)
        if (($lowercasePassword == $lc_user) || ($lowercasePassword == strrev($lc_user)) ||
            ($leetspeakPassword == $lc_user) || ($leetspeakPassword == strrev($lc_user))) {
            return 'The password is based on the username.';
        }

        // count how many lowercase, uppercase, and digits are in the password
        $uppercase = $lowercase = $numeric = $other = 0;
        for ($i = 0, $j = strlen($password); $i < $j; $i++) {
            $c = substr($password,$i,1);
            if (preg_match('/^[[:upper:]]$/',$c)) {
                $uppercase++;
            } elseif (preg_match('/^[[:lower:]]$/',$c)) {
                $lowercase++;
            } elseif (preg_match('/^[[:digit:]]$/',$c)) {
                $numeric++;
            } else {
                $other++;
            }
        }

        // the password must have more than two characters of at least
        // two different kinds
        $max = $j - 2;
        if ($uppercase > $max) {
            return 'The password has too many upper case characters.';
        }
        if ($lowercase > $max) {
            return 'The password has too many lower case characters.';
        }
        if ($numeric > $max) {
            return 'The password has too many numeral characters.';
        }
        if ($other > $max) {
            return 'The password has too many special characters.';
        }

        // the password must not contain a dictionary word
        if (is_readable($word_file)) {
            if ($fileHandler = fopen($word_file, 'r')) {
                $found = false;
                while (! ($found || feof($fileHandler))) {
                    $word = preg_quote(trim(strtolower(fgets($fileHandler, 1024))), '/');
                    if (preg_match("/^$word$/", $lowercasePassword) ||
                        preg_match("/^$word$/", $leetspeakPassword)) {
                        $found = true;
                    }
                }
                fclose($fileHandler);
                if ($found) {
                    return 'The password is based on a dictionary word.';
                }
            }
        }

        return null;
    }

}