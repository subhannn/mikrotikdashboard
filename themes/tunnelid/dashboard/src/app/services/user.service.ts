import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

import { ApiService } from './api.service';

import * as $ from 'jquery';

@Injectable()
export class UserService {

  private currentUserSubject = new BehaviorSubject<any>(new User);
  public currentUser = this.currentUserSubject.asObservable()

  constructor(
  	private apiService: ApiService,
  ) { }

  userObj: User;

  populate(){
  	this.apiService.request('onCheckUser')
  		.subscribe(
  			response => this.setAuth(response),
  			err => this.purgeAuth()
  		)
  }

  setAuth(user: User){
  	// hide loader
  	$(".preloader").fadeOut()

  	// save active user
  	this.currentUserSubject.next(user)
    this.userObj = user
  }

  checkPermissions(index){
    if(typeof this.userObj != 'undefined' && this.userObj.permissions.indexOf(index) >= 0){
      return true;
    }

    return false;
  }

  signout(){
    this.apiService.request('onLogout', {
      redirect: '/'
    }, 'session')
      .subscribe(
        response => window.location.href = response.X_OCTOBER_REDIRECT,
        err => this.purgeAuth()
      )
  }

  purgeAuth(){

  }
}

export class User{
  id: number;
  email: string;
  fullname: string;
  image: string;
  permissions: string[];
}