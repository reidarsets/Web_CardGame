var host = getCookie('servHost');
var client = new WebSocket(`ws://${host}:8000`);

client.onmessage = function (e) {
  let msg = JSON.parse(e.data);
  let status = msg['status'];
  if (status === 'FAIL') {
    document.getElementById('perror').innerHTML = msg['message'];
    document.getElementsByName('password')[0].value = "";
    document.getElementsByName('password')[1].value = "";
    document.getElementsByName('confirm')[0].value = "";
  }
  else {
    if (document.getElementById('remind').style.display === 'flex') {
      transit('remind', 'login');
    }
    else {
      if (document.getElementById('login').children[0].children[0].value !== "")
        document.getElementById('login').children[0].submit();
      else
        document.getElementById('registration').children[0].submit();
    }
  }
}

function transit(from, to) {
  document.getElementById('perror').innerHTML = '';
  document.getElementById(from).style.display = 'none';
  document.getElementById(to).style.display = 'flex';
}

function processLogin() {
  let login = document.getElementsByName('username')[0].value;
  let password = document.getElementsByName('password')[0].value;
  let arr = {operation: 'authorization', login: login, password: password};
  setTimeout(() => {
    client.send(JSON.stringify(arr));
   }, 10);
  return false;
}

function processRegistration() {
  let password = document.getElementsByName('password')[1].value;
  let confirm = document.getElementsByName('confirm')[0].value;
  if (password !== confirm) {
    document.getElementById('perror').innerHTML = 'Passwords do not match!';
    return false;
  }
  let login = document.getElementsByName('username')[1].value;
  let name = document.getElementsByName('name')[0].value;
  let email = document.getElementsByName('email')[0].value;
  let arr = {operation: 'registration', username: login, password: password, name: name, email: email};
  setTimeout(() => {
    client.send(JSON.stringify(arr));
   }, 10);
  return false;
}

function processRemind() {
  let login = document.getElementsByName('username')[2].value;
  let arr = {operation: 'remind', username: login};
  setTimeout(() => {
    client.send(JSON.stringify(arr));
  }, 10);
  return false;
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