import { Component, OnInit, Input, Output, EventEmitter, OnChanges, SimpleChanges, SimpleChange } from '@angular/core';

import { ModalService } from '../../services/modal.service';

import * as $ from 'jquery';

@Component({
  selector: 'app-inline-edit',
  templateUrl: './inline-edit.component.html',
  styleUrls: ['./inline-edit.component.css']
})
export class InlineEditComponent implements OnInit, OnChanges{
	
	constructor(
		private modal: ModalService,
	){}

	public isDisplay = true;
	public isError = false;

    @Input() text: string;
    @Input() confirmMessage: string;
    @Input() min: number;
    @Input() max: number;
    @Input() charLimit: string;

    @Output() onEdit = new EventEmitter<InlineEvent>();

    private _firstText: string;
    ngOnInit(){
    	this._firstText = this.text
    }

    ngOnChanges(changes: SimpleChanges){
    	const text: SimpleChange = changes.text
    	this._firstText = text.currentValue
    }

    edit(el: HTMLElement): void {
        this.isDisplay = false;

        setTimeout(() => {
            el.focus();
        }, 100);
    }

    onChange(){
    	if(this.text.length < this.min){
    		this.isError = true
    	}else if(this.text.length > this.max){
    		this.isError = true
    	}else{
    		this.isError = false
    	}
    }

    limitChar(event: KeyboardEvent){
    	if(!this.charLimit)
    		return

    	const key: string = event.key;
    	var regex = ''
    	if(this.charLimit.indexOf('l') > -1)
    		regex += 'a-z'
    	if(this.charLimit.indexOf('u') > -1)
    		regex += 'A-Z'
    	if(this.charLimit.indexOf('d') > -1)
    		regex += '0-9'
    	if(this.charLimit.indexOf('s') > -1)
    		regex += '!@#$_-'
    	
    	if(regex){
    		var re = new RegExp("["+regex+"]"); 
    		if(!key.match(re) || this.text.length > this.max){
	    		event.preventDefault()
	    		return false
	    	}
    	}
    }

    editDone(){
    	if(this.isError)
    		return false

    	if(!this.text){
    		this.isDisplay = true
    		this.text = this._firstText
    		return false
    	}

    	if(this._firstText != this.text && !this.isDisplay){
    		if(this.confirmMessage){
			    this.modal.confirmation(this.confirmMessage)
			      .subscribe(result => {
			        if(result == true){
			         	this.onEdit.emit(<InlineEvent>{
                             text: this.text,
                             source: this
                         })
			        }else{
			        	this.text = this._firstText
			        }
			      })
    		}else{
    			this.onEdit.emit(<InlineEvent>{
                     text: this.text,
                     source: this
                 })
    		}
    	}
    	this.isDisplay = true
    }
    
    reset(){
    	this.isDisplay = true
    	this.isError = false
    	this.text = this._firstText
    }
}

export class InlineEvent{
    text: string;
    source: InlineEditComponent;
}