import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

import { ApiService } from './api.service';

import * as $ from 'jquery';

@Injectable()
export class UserService {

  private currentUserSubject = new BehaviorSubject<any>(Object);
  public currentUser = this.currentUserSubject.asObservable()

  constructor(
  	private apiService: ApiService
  ) { }

  populate(){
  	this.apiService.request('onCheckUser')
  		.subscribe(
  			response => this.setAuth(response),
  			err => this.purgeAuth()
  		)
  }

  setAuth(user: Object = {}){
  	// hide loader
  	$(".preloader").fadeOut()

  	// save active user
  	this.currentUserSubject.next(user)
  }

  purgeAuth(){

  }
}
