function perform_end_turn() {
    if (turn % 2 == 0) {
        let OponentInfo = JSON.parse(getCookie('OponentInfo'));
        let msg = {operation: "EndTurn", player: OponentInfo["OponentLogin"]};
        client.send(JSON.stringify(msg));
        turn++;
        if (enemyStones < 6) {
            enemyStones += 1;
            RecoverStones(enemyStones, 'stone1');
        }
        end_turn();
    }
}
function end_turn() {
    if (turn % 2 == 0)
        take_card('playerHand');
    else
        take_card('oponentHand');
    rotateCoin()
    clearInterval(id)
    i = 0
    document.getElementById("myBar").style.width = "0%"
    setTimeout(function() {
        document.getElementById("myBar").style.width = "600px";
        document.getElementById("myBar").style.backgroundColor = "green";
        time()
    }, 500)
}