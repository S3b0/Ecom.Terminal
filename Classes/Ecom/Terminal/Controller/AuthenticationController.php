<?php
namespace Ecom\Terminal\Controller;

/*
 * This file is part of the Ecom.Terminal package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message as Msg;
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
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $lang;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Service
     */
    protected $languageService;

    /**
     * Initializes the controller before invoking an action method.
     */
    public function initializeAction()
    {
        $detector = new \TYPO3\Flow\I18n\Detector();
        $this->lang = $detector->detectLocaleFromHttpHeader($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->settings['i18n']['defaultLocale'] = $this->lang->getLanguage();
        $this->languageService->getConfiguration()->setCurrentLocale($this->lang);
    }

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
        $this->addFlashMessage($this->translate('fm.loginSuccess.message'), $this->translate('fm.loginSuccess.title'));
        if ($originalRequest !== null) {
            $this->redirectToRequest($originalRequest);
        }
        $this->redirect('index', 'Administration');
    }

    protected function onAuthenticationFailure(\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception = null)
    {
        $this->addFlashMessage($exception->getMessage(), $this->translate('fm.authenticationFailed.title'), Msg::SEVERITY_ERROR, [], $exception->getCode());
    }

    /**
     * Logs all active tokens out and redirects the user to the login form
     *
     * @return void
     */
    public function logoutAction()
    {
        parent::logoutAction();
        $this->addFlashMessage($this->translate('fm.logoutSuccess.message'), $this->translate('fm.logoutSuccess.title'));
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
            $this->addFlashMessage($this->translate('fm.validation.username.tooShort.message', [ 'length' => self::MIN_USERNAME_LENGTH ]), $this->translate('fm.validation.username.tooShort.title'), Msg::SEVERITY_WARNING);
        } elseif (!preg_match('/^[a-z][a-z0-9-_]+$/i', $identifier)) {
            $this->addFlashMessage($this->translate('fm.validation.username.invalidChars.message'), $this->translate('fm.validation.username.invalidChars.title'), Msg::SEVERITY_WARNING);
        } elseif ($this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($identifier, 'DefaultProvider') instanceof \TYPO3\Flow\Security\Account) {
            $this->addFlashMessage($this->translate('fm.validation.username.duplicate.message'), $this->translate('fm.validation.username.duplicate.title', [ 'identifier' => $identifier ]), Msg::SEVERITY_WARNING);
        } elseif ($errorMessage = $this->checkPassword($identifier, $password)) {
            $this->addFlashMessage($errorMessage, $this->translate('fm.validation.password.failed.title'), Msg::SEVERITY_WARNING);
        } elseif ($password !== $passwordCheck) {
            $this->addFlashMessage($this->translate('fm.validation.password.dblCheckFailed.message'), $this->translate('fm.validation.password.dblCheckFailed.title'), Msg::SEVERITY_WARNING);
        } else {
            switch ($role) {
                case 5380:
                    $roles = ['Ecom.Terminal:Administrator'];
                    break;
                default:
                    $roles = ['Ecom.Terminal:User'];
            }
            // create an account with password and add it to the accountRepository
            $account = $this->accountFactory->createAccountWithPassword($identifier, $password, $roles);
            $this->accountRepository->add($account);
            // add a message and redirect to login form
            $this->addFlashMessage('', $this->translate('fm.accountCreated.title', [ 'name' => $identifier ]));
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
            return $this->translate('fm.validation.password.tooShort.message', [ 'length' => self::MIN_PASSWORD_LENGTH ]);
        }

        // the password can't be the username (or reversed username)
        if (($lowercasePassword == $lc_user) || ($lowercasePassword == strrev($lc_user)) ||
            ($leetspeakPassword == $lc_user) || ($leetspeakPassword == strrev($lc_user))) {
            return $this->translate('fm.validation.password.usernameSimilar.message');
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
            return $this->translate('fm.validation.password.tooManyUCChars.message');
        }
        if ($lowercase > $max) {
            return $this->translate('fm.validation.password.tooManyLCChars.message');
        }
        if ($numeric > $max) {
            return $this->translate('fm.validation.password.tooManyNumChars.message');
        }
        if ($other > $max) {
            return $this->translate('fm.validation.password.tooManySpecChars.message');
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
                    return $this->translate('fm.validation.password.dictBased.message');
                }
            }
        }

        return null;
    }

    /**
     * @param string $id
     * @param array  $arguments
     *
     * @return string
     */
    private function translate($id, array $arguments = [])
    {
        return $this->translator->translateById($id, $arguments, null, $this->lang, 'Auth', $this->request->getControllerPackageKey());
    }

}