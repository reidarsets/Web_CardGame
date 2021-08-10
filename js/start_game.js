let turn = 1;

function start_game() {
    let OponentInfo = JSON.parse(getCookie('OponentInfo'));
    turn = OponentInfo["Turn"];
    
    let coinFace = document.getElementById('coinFace');
    coinFace.style.position = "fixed"
    coinFace.style.left = "47.5%"
    coinFace.style.transform = 'rotateX(' + (12+turn)*180 + 'deg)';
    let coinBack = document.getElementById('coinBack');
  
    // Create an effect of throwing a coin
    coinFace.style.width = '180px';
    coinFace.style.height = '180px';
    coinFace.style.right = '10px';
  
    coinBack.style.width = '180px';
    coinBack.style.height = '180px';
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
        let start1 = Date.now(); // запомнить время начала
        let timer_1 = setInterval(function() {
    
            let timePassed1 = Date.now() - start1;
            if(timePassed1 < 1000){
                coinFace.style.left = timePassed1 / 11 + '%';
            }
            if (timePassed1 > 3000) {
                clearInterval(timer_1)
                coinFace.onclick = perform_end_turn;
                time()
            }
        }, 1)
    }, 2100)
}