class Hero {
    constructor() {
      this.health = 20;
      this.type = "Hero";
    }
    take_damage(damage) {
      this.health -= damage;
    }
}

class Card {
    constructor(name) {
        this.type = "Card";
        this.name = name;

        this.path = "assets/images/Characters/" + name + ".png";
        this.element = document.createElement('div');

        switch (name) {
          case 'Avengers':
            this.attack = 5;
            this.health = 6;
            this.cost = 4;
            break;
          case 'Battlefield':
            this.attack = 2;
            this.health = 6;
            this.cost = 3;
            break;
          case 'BlackBolt':
            this.attack = 2;
            this.health = 2;
            this.cost = 1;
            break;
          case 'BlackWidow':
            this.attack = 4;
            this.health = 2;
            this.cost = 2;
            break;
          case 'CapitainAmerica':
            this.attack = 4;
            this.health = 4;
            this.cost = 2;
            break;
          case 'Collapse':
            this.attack = 6;
            this.health = 2;
            this.cost = 3;
            break;
          case 'Conflict':
            this.attack = 6;
            this.health = 6;
            this.cost = 4;
            break;
          case 'DayWatch':
            this.attack = 2;
            this.health = 2;
            this.cost = 1;
            break;
          case 'Defeat':
            this.attack = 5;
            this.health = 2;
            this.cost = 3;
            break;
          case 'IronManWithStones':
            this.attack = 6;
            this.health = 4;
            this.cost = 4;
            break;
          case 'Nightcrawler':
            this.attack = 2;
            this.health = 1;
            this.cost = 1;
            break;
          case 'Rage':
            this.attack = 5;
            this.health = 1;
            this.cost = 2;
            break;
          case 'Reborn':
            this.attack = 4;
            this.health = 3;
            this.cost = 3;
            break;
          case 'Spider-man':
            this.attack = 2;
            this.health = 2;
            this.cost = 2;
            break;
          case 'Thing':
            this.attack = 2;
            this.health = 5;
            this.cost = 3;
            break;
          case 'Vision':
            this.attack = 5;
            this.health = 5;
            this.cost = 3;
            break;
          case 'WarMachine':
            this.attack = 6;
            this.health = 5;
            this.cost = 3;
            break;
          case 'WarOfTheRealms':
            this.attack = 5;
            this.health = 4;
            this.cost = 4;
            break;
          case 'Wolverine':
            this.attack = 3;
            this.health = 3;
            this.cost = 2;
            break;
          case 'card_back':
            this.attack = 0;
            this.health = 0;
            this.cost = 0;
            break;
        }
        if (name !== 'card_back') {
          let defense = document.createElement('div');
          defense.id = 'num';
          defense.innerHTML = this.health;
          this.element.appendChild(defense);
        }
        this.element.className = 'card';
        this.element.style.backgroundImage = "url(\"" + this.path + "\")";
    }
    give_damage(target, mode) {
      target.take_damage(this.attack);
      if (target.type === "Hero") {
          if (mode !== 'silent')
            checkGame();
      }
      return;
    }
    take_damage(damage) {
      this.health -= damage;
      if (this.health <= 0) {
        let parent = this.element.parentNode;
        parent.removeChild(this.element);
        if (parent.id === 'playerField') {
          for (let i = 0; i < playerField.length; ++i) {
            if (playerField[i].element === this.element)
              playerField.splice(i, 1);
          }
        }
        else {
          for (let i = 0; i < enemyField.length; ++i) {
            if (enemyField[i].element === this.element)
              enemyField.splice(i, 1);
          }
        }
      }
      else {
        this.element.children[0].innerHTML = this.health;
      }
    }
}
