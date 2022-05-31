class Card {
	constructor( data ){
		this.title = data.title;
		this.content = data.content;
		this.points = data.points;
		this.image = data.image;
		this.used = false;
		this.id = this.GenerateID()	
	}	
	
	GenerateID(){
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
			return v.toString(16);
		  });		 
	}

}
