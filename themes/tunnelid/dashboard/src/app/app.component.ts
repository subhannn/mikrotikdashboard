import { Component, OnInit } from '@angular/core';

import { UserService } from './services/user.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit{
  title = 'Dashboard Admin User2';

  constructor (
  	private userService: UserService
  ) {}

  currentDate: number = Date.now();

  ngOnInit() {
    this.userService.populate()
  }
}
