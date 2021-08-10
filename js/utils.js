var host = getCookie('servHost');
var client = new WebSocket(`ws://${host}:8000`);

let OponentInfo = JSON.parse(getCookie('OponentInfo'));
let PlayerInfo = JSON.parse(getCookie('PlayerInfo'));

setTimeout(() => {
  let msg = {operation: 'UpdateID', login: PlayerInfo["PlayerLogin"], name: PlayerInfo["PlayerName"]};
  client.send(JSON.stringify(msg));
}, 200);

function ReduceStones(card, stones, id) {
  if (id === 'stone2') {
    playerStones -= card.cost;
    stones = playerStones;
  }
  else {
    enemyStones -= card.cost;
    stones = enemyStones;
  }
  let children = document.getElementById(id).children;
  for (let i = stones; i < 6; ++i) {
      children[i].className = "DeStone";
  }
}

function RecoverStones(stones, id) {
  if (id === 'stone2')
    stones = playerStones;
  else 
    stones = enemyStones;
  
  let children = document.getElementById(id).children;
  for (let i = stones - 1; i < 6; ++i) {
      if (i < 0) i = 0;
      children[i].className = children[i].className.replace("DeStone", "");
      break;
  }

}

client.onmessage = function (e) {
  let msg = JSON.parse(e.data);
  switch (msg["operation"]) {
    case "BattleFinish":
      document.cookie = "OponentInfo=" + getCookie('OponentInfo') + "; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT";
      document.cookie = `user=${PlayerInfo['PlayerLogin']}; path=/; expires=0`;
      document.cookie = "PlayerInfo=" + JSON.stringify(PlayerInfo) + "; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT";
      document.getElementById('finish').submit();
      break;
    case "playCard":
      document.getElementById('oponentHand').removeChild(document.getElementById('oponentHand').firstChild);
      let index = Math.floor(Math.random() * enemyHand.length - 1);
      if (index < 0) index = 0;
      enemyHand.splice(index, 1);
      enemyField.push(new Card(msg['card']));
      document.getElementById('oponentField').appendChild(enemyField[enemyField.length - 1].element);
      document.getElementsByClassName("handUp")[0].style.left = "calc(50% - " + enemyHand.length + "*(150px+5)/2)";
      document.getElementsByClassName("handUp2")[0].style.left = "calc(50% - " + enemyField.length + "*(150px+5)/2)";
      ReduceStones(enemyField[enemyField.length - 1], enemyStones, 'stone1');

      if (playerField.length < 1) {
        enemyField[enemyField.length - 1].give_damage(player, 'silent');
        document.getElementById('php').innerHTML = "HP: " + player.health + "/20";
      }
      else {
        enemyField[enemyField.length - 1].give_damage(playerField[0], 'silent');
      }
      break;
    case "EndTurn":
      turn += 1;
      if (playerStones < 6) {
        playerStones += 1;
        RecoverStones(playerStones, 'stone2');
      }
      end_turn();
      break;
    default:
      break;
  }
}

function rotateCoin() {
  let coinFace = document.getElementById('coinFace');
  coinFace.style.transform = 'rotateX(' + ((turn*5)*180) + 'deg)';
  let coinBack = document.getElementById('coinBack');

  // Create an effect of throwing a coin
  coinFace.style.width = '110px';
  coinFace.style.height = '110px';
  coinFace.style.right = '10px';

  coinBack.style.width = '110px';
  coinBack.style.height = '110px';
  setTimeout(() => {
    coinFace.style.width = '80px';
    coinFace.style.height = '80px';
    coinFace.style.right = '20px';
    coinBack.style.width = '80px';
    coinBack.style.height = '80px';
  }, 1000);


  // Reset the animation
  setTimeout(() => {
    coinFace.style.transition = 'all 2s';
  }, 2100);
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

function fillDeck(deck) {
  deck.push(new Card('Avengers'));
  deck.push(new Card('Battlefield'));
  deck.push(new Card('BlackBolt'));
  deck.push(new Card('BlackWidow'));
  deck.push(new Card('CapitainAmerica'));
  deck.push(new Card('Collapse'));
  deck.push(new Card('Conflict'));
  deck.push(new Card('DayWatch'));
  deck.push(new Card('Defeat'));
  deck.push(new Card('IronManWithStones'));
  deck.push(new Card('Nightcrawler'));
  deck.push(new Card('Rage'));
  deck.push(new Card('Reborn'));
  deck.push(new Card('Spider-man'));
  deck.push(new Card('Thing'));
  deck.push(new Card('Vision'));
  deck.push(new Card('WarMachine'));
  deck.push(new Card('WarOfTheRealms'));
  deck.push(new Card('Wolverine'));
}

function playedCard(name, player, socket) {
  let msg = {operation: 'playCard', player: player, card: name};
  socket.send(JSON.stringify(msg));
}
