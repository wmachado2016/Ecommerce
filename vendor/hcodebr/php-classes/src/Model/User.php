<?php 

namespace Hcode\Model;

use \Hcode\Model;
use \Hcode\DB\Sql;

class User extends Model {

	const SESSION = "User";
	const SECRET = "HcodePhp7_secret";

	protected $fields = [
		"iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister"
	];

	public static function login($login, $password):User
	{

		$db = new Sql();

		$results = $db->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0) {
			throw new \Exception("Não foi possível fazer login.");
		}

		$data = $results[0];

		if ($data["despassword"] ==  $password ) {

			$user = new User();
			$user->setData($data);
			
			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {

			throw new \Exception("Não foi possível fazer login.");

		}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

	public static function verifyLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION])
			|| 
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
			(bool)$_SESSION[User::SESSION]["iduser"] !== $inadmin
		) {
			
			header("Location: /admin/login");
			exit;

		}

	}
	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
	}

	public function save(){
		$sql = new Sql();
		$result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
		array(
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));
		
		$this->setData($result);
	}

	public static function getForgot($email){
		$sql = new SQl();

		$results= $sql->select("select * from tb_persons a ineer join tb_users b using(idperson) where a.desemail = :email",
								array(
									":email"=>$email
								));

		if(counts($results) === 0){
			throw new \Exception("Não foi possível recuperar senha.");
		}
		else{
			$data = $results[0];
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
							array(
							":iduser"=> $data["iduser"],
							":desip"=> $_SERVER["REMOTE_ADDR"]
						));
			if(count($results2) === 0){
				throw new \Exception("Não foi possível recuperar senha.");
			}
			else{
				$dataRecovery = $results2[0]; 
				
				$code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"],openssl_get_cipher_methods(),User::SECRET));

				$link = "http://www.hcodecommerce.com.br/admin/forget/reset?code=$code";
			}
		}

	}

}

?>