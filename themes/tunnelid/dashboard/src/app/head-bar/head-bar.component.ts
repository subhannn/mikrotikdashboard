import { Component, OnInit } from '@angular/core';

import { UserService } from '../services/user.service';

@Component({
  selector: '[app-head-bar]',
  templateUrl: './head-bar.component.html',
  styleUrls: ['./head-bar.component.css']
})
export class HeadBarComponent implements OnInit {

  constructor(
  	private userService: UserService
  ) { }

  currentUser;

  ngOnInit() {
  	this.userService.currentUser.subscribe(
  		(userData) => {
  			this.currentUser = userData
  		}
  	)
  }

  onSignOut(){
    this.userService.signout()
  }
}
