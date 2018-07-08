import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

import { ApiService, Config } from './api.service';

import * as $ from 'jquery';

@Injectable()
export class UserService {

  private currentUserSubject = new BehaviorSubject<any>(new User);
  public currentUser = this.currentUserSubject.asObservable()

  public isLoggedIn = false;

  constructor(
  	private apiService: ApiService
  ) { }

  userObj: User;

  populate(onSuccess: (user) => any){
    var $this = this
  	this.apiService.request('onCheckUser', <Config>{
      success: (response) => {
        this.setAuth(response)
        onSuccess(response)
      }
    })
  }

  setAuth(user: User){
  	// hide loader
  	$(".preloader").fadeOut()

  	// save active user
  	this.currentUserSubject.next(user)
    this.userObj = user
    this.isLoggedIn = true
  }

  signout(){
    this.apiService.request('onLogout', <Config>{
      data: {
        redirect: '/'
      },
      octoberComponent: 'session',
      success: (response) => {
        window.location.href = response.X_OCTOBER_REDIRECT
      }
    })
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