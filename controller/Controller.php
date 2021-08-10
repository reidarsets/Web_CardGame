<?php
class Controller {
  public $view;

  public function __construct() {
    $this->view = new View(__DIR__ . '/../view/templates/startscreen.html');
  }

  public function transit() {
    if (isset($_POST["transit"])) {
      switch($_POST["transit"]) {
        case "exit":
          unset($_SESSION["user"]);
          setcookie("user", "", time() - 3600);
          $_SESSION["transition"] = "startscreen";
          break;
        default:
          $_SESSION["transition"] = $_POST["transit"];
          break;
      }
    }
    else {
      if (isset($_COOKIE['OponentInfo'])) {
        $_SESSION["transition"] = "game";
      }
      else if (isset($_POST["username"])) {
        $_SESSION["transition"] = "lobby";
        $_SESSION["user"] = $_POST["username"];
        setcookie("user", $_POST["username"], 0, "/");
      }
      else if (isset($_COOKIE["user"])) {
        $_SESSION["transition"] = "lobby";
        $_SESSION["user"] = $_COOKIE["user"];
      }
    }
    $this->view = new View(__DIR__ . "/../view/templates/" . $_SESSION["transition"] . ".html");
  }

  public function execute() {
    $this->view->render();
  }
}
?>
