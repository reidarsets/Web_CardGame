function end_game() {
    let text = document.querySelector("div#Ylogin > div").innerHTML;
    let temp = text.split(" ");
    let HP = temp[1].split("/");
    if(Number(HP[0]) <= 0) {
        let result = document.createElement("p");
        result.innerHTML = "You lose";
        result.style.color = "white";
        result.style.position = "relative";
        document.getElementById("myProgress").remove();
        result.style.textAlign = "center";
        result.style.paddingTop = "10%";

        result.style.width = "100%";

        let start1 = Date.now(); // запомнить время начала
        let timer_1 = setInterval(function() {
            let timePassed1 = Date.now() - start1;
            if(timePassed1 <= 1000) {
                result.style.fontSize = "" + (100 + timePassed1*0.1) + "px";
                result.style.opacity = "" + (0.2 + timePassed1*0.0008);
                document.getElementsByTagName("body")[0].append(result);
            }
            if (timePassed1 >= 3750) {
                clearInterval(timer_1);
            }
        }, 1)
    }
    let text_enemy = document.querySelector("div#Elogin > div").innerHTML;
    let temp_enemy = text_enemy.split(" ");
    let HP_enemy = temp_enemy[1].split("/");
    if(Number(HP_enemy[0]) <= 0) {
        let result = document.createElement("p");
        result.innerHTML = "You win";
        result.style.color = "white";
        result.style.position = "relative";
        document.getElementById("myProgress").remove();
        result.style.textAlign = "center";
        result.style.paddingTop = "10%";

        let start1 = Date.now(); // запомнить время начала
        let timer_1 = setInterval(function() {
            let timePassed1 = Date.now() - start1;
            if(timePassed1 <= 1000) {
                result.style.fontSize = "" + (100 + timePassed1*0.1) + "px";
                result.style.opacity = "" + (0.2 + timePassed1*0.0008);
                document.getElementsByTagName("body")[0].append(result);
            }
            if (timePassed1 >= 3750) {
                clearInterval(timer_1);
            }
        }, 1)
    }
}