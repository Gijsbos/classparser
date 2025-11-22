<?php
declare(strict_types=1);

use Test\TestTrait;

include_once "./tests/Files/TestTrait.php";

/**
 * AccessToken
 */
class AccessToken
{
    use TestTrait;

    /**
     * @var string token
     * 
     * @regexp      "/^[\w\-\.\~\+]{64,128}$/"
     * @generate    <method;\WDS\API\OAUTH2\AccessToken::generateToken>
     */
    #[TestAttribute("/^[\w\-\.\~\+]{64,128}$/")]
    public $token;
    
    /**
     * @var string scopes
     * 
     * @encrypt
     * @regexp      "/^[\w\.\:\-\, ]*$/"
     * @generate    <random;array;"oauth2_user,oauth2_client","oauth2_user">
     */
    #[TestAttribute("/^[\w\.\:\-\, ]*$/")]
    public $scopes;

    /**
     * @var int expires
     * 
     * @generate    <method;\WDS\API\OAUTH2\AccessToken::getExpiresTimestamp>
     */
    public $expires;

    /**
     * @var string subjectId
     */
    public $subjectId;

    /**
     * @var string audience
     */
    public $audience;

    /**
     * @var string issuer
     */
    public $issuer;

    /**
     * @var string ownerId
     * 
     * @regexp "/^.{1,64}$/"
     */
    #[TestAttribute("/^.{1,64}$/")]
    public $ownerId;

    /**
     * @var string tokenClass
     */
    public $tokenClass;

    /**
     * @var datetime created
     * 
     * @generate    <date>
     */
    public $created;

    /**
     * subjectIsUser
     */
    #[TestAttribute("/^.{1,33}$/")]
    public function subjectIsUser()
    {
        if(!is_string($this->subjectId))
            throw new Exception("Operation failed, property 'subjectId' not set");

        return str_starts_with($this->subjectId, "user_");
    }

    /**
     * apple
     */
    #[TestAttribute("/^.{1,44}$/")]
    public function apple()
    {
        if(!is_string($this->subjectId))
            throw new Exception("Operation failed, property 'subjectId' not set");

        return str_starts_with($this->subjectId, "user_");
    }
}