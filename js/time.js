let id;
var i = 0;
function time() {
  if (i == 0) {
    i = 1;
    var msec = 0;
    var elem = document.getElementById("myBar");
    let width = 100;
    id = setInterval(frame, 300); // 30 seconds round
    function frame() {
      msec += 300;
      if (width <= 0) {
        clearInterval(id);
        i = 0;

        perform_end_turn();

        let cards = document.querySelectorAll("div.SteckC > div.card")
        if (turn % 2 == 0)
          take_card('playerHand');
        else
          take_card('oponentHand')

      } else {
        if (msec > 20000) document.getElementById("myBar").style.backgroundColor = "orange";
        if (msec > 25000) document.getElementById("myBar").style.backgroundColor = "red";
        width--;
        elem.style.width = width + "%";
      }
    }
  }
}
function take_card(hand) {
    if (hand === 'oponentHand') {
      Deck.push(new Card('card_back'));
      document.getElementsByClassName('SteckC')[0].appendChild(Deck[1].element);
    }
    else {
      let index = Math.floor(Math.random() * playerDeck.length - 1);
      if (index < 0) index = 0;
      document.getElementsByClassName('SteckC')[0].appendChild(playerDeck[index].element);
      let card = playerDeck.splice(index, 1);
      Deck.push(card);
    } 

    let new_card = document.getElementsByClassName("SteckC")[0].lastChild;
    let element = Deck.pop();
    if (hand === 'oponentHand') {
      enemyHand.push(element);
    }
    else {
      playerHand.push(element);
    }

    let start1 = Date.now(); // запомнить время начала
    let timer_1 = setInterval(function() {
    
      let timePassed1 = Date.now() - start1;
      if(timePassed1 < 300){
        new_card.style.left = timePassed1 / 6.3 + '%';
      }
      if (timePassed1 > 800) {
        clearInterval(timer_1);
        let start2 = Date.now(); // запомнить время начала
        let timer_2 = setInterval(function() {
          let timePassed2 = Date.now() - start2;
          if(timePassed2 < 300){
            new_card.style.top += (timePassed2 + 0.9) / 0.1 + '%';
          }
          if (timePassed2 > 300) {
            clearInterval(timer_2);
            let your_hand = document.getElementById(hand);
            your_hand.append(new_card)
            new_card.style.removeProperty('left')
            new_card.style.removeProperty('top')
            

            let cards = document.querySelectorAll("div.hand > div.card")
            new_card.setAttribute("id", ''+cards.length)

            document.getElementById("myBar").style.backgroundColor = "green";
            }
        }, 1)
      }
    }, 1)  
}