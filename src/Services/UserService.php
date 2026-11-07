<?php
declare(strict_types=1);

namespace App\Services;
use App\Repositories\UserRepo;


class UserService{
private UserRepo $userRepo;
public function __construct(UserRepo $userRepo)
{
    $this->userRepo = $userRepo;
}

public function findUserById(int $id){
 if($id <=0) return null;
 $user = $this->userRepo->getUserById($id);
 return $user === false ? null : $user;
}

public function findUserByEmail(string $email){
 if($email === "") return null;
 $user = $this->userRepo->findUserByEmail($email);
 return $user === false ? null : $user;
}


public function createUser(string $username, string $password_hash, string $email){
if(trim($username) === "" || $password_hash === "" || trim($email) === "") {
    return null;
}
 $user = $this->userRepo->createUser($username,$email,$password_hash);
 return $user === false ? null : $user;
}

public function updateUserById(int $id,string $username, string $password_hash, string $email){
 if(trim($username) === "" || $password_hash === "" || trim($email) === "") {
    return null;
}
 $user = $this->userRepo->updateUserById($id,$username,$password_hash,$email);
 return $user === false ? null : $user;
}

public function deleteUserById(int $id){
 if($id <=0) return null;
 $user = $this->userRepo->deleteUserById($id);
 return $user === false ? null : $user;
}


}

?>