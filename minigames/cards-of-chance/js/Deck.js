class Deck{
	constructor(){
		this.topCard = 0;
		this.library = [];	
		this.id = this.GenerateID();	
	}
	
	addCard(t, c, v){
		let _card = new Card(t, c, v);
		this.library.push(_card);
	}
	
	shuffle(){
		var currentIndex = this.library.length, temporaryValue, randomIndex;

		while (0 !== currentIndex){
			randomIndex = Math.floor(Math.random() * currentIndex);
			currentIndex -= 1;
			
			temporaryValue = this.library[currentIndex];			
			this.library[currentIndex] = this.library[randomIndex];
			this.library[randomIndex] = temporaryValue;			
		}
	
	}

	GenerateID(){
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
			return v.toString(16);
		  });		 
	}
	
}