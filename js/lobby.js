var host = getCookie('servHost');
var client = new WebSocket(`ws://${host}:8000`);

var user = getCookie('user');
document.cookie = "user=" + user + "; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT";
let hero = 0;

var searching = false;
var cardDesc = ['', "", ""];

client.onmessage = function (e) {
    let msg = JSON.parse(e.data);
    switch (msg['operation']) {
      case 'InfoRespond':
        document.getElementById('name').innerHTML = msg['name'];
        document.getElementById('wins').innerHTML = msg['win'];
        document.getElementById('loses').innerHTML = msg['lose'];
        break;
      case 'Searching':
        searching = true;
        break;
      case 'OponentInfo':
        searching = false;
        msg = JSON.stringify(msg);
        document.cookie = `OponentInfo=${msg}; path=/; expires=0`;
        document.getElementById('startGame').submit();
        break;
      default:
        break;
    }
}

function findOponent() {
  let tmp_name = document.getElementById('name').innerHTML;
  document.cookie = `PlayerInfo={"PlayerName":"${tmp_name}","PlayerLogin":"${user}","PlayerHero":"${hero}"}; path=/; expires=0`;
  if (searching) { // Stop searching query
    let arr = {operation: 'Delete', from: 'search_lobby', subject: 'serv_id', condition: 'myID'};
    client.send(JSON.stringify(arr));
    searching = false;
  }
  else { // Send a searching query
    let characters = document.getElementsByName('fb');
    if (characters[0].checked)
      hero = 0;
    else if (characters[1].checked)
      hero = 1;
    else if (characters[2].checked)
      hero = 2;
    else {
      return false;
    }
    let arr = {operation: 'MoveToSearchLobby', hero: hero};
    client.send(JSON.stringify(arr));
  }
  return false;
}

function getItems() {
    let arr = {operation: 'GETinfo', target: user};
    client.send(JSON.stringify(arr));
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
}

var characters = document.getElementById('chose_hero').children;
for (let i = 0; i < characters.length; ++i) {
    characters[i].onclick = () => {
        document.getElementById('des').innerHTML = cardDesc[i];
    }
}
setTimeout(getItems, 100);
