import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';

import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {MatProgressBarModule, MatProgressSpinnerModule, MatDialogModule,MatSlideToggleModule} from '@angular/material';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { HeadBarComponent } from './head-bar/head-bar.component';
import { SideBarComponent } from './side-bar/side-bar.component';
import { HomeComponent } from './home/home.component';
import { TunnelComponent } from './tunnel/tunnel.component';

import { UserService } from './services/user.service';
import { IpService } from './services/ip.service';
import { ModalService, ConfirmationModalComponent } from './services/modal.service';

@NgModule({
  declarations: [
    AppComponent,
    HeadBarComponent,
    SideBarComponent,
    HomeComponent,
    TunnelComponent,
    ConfirmationModalComponent
  ],
  imports: [
    BrowserAnimationsModule,
    MatProgressBarModule,
    MatProgressSpinnerModule,
    MatDialogModule,
    MatSlideToggleModule,

    BrowserModule,
    HttpClientModule,
    AppRoutingModule,
  ],
  providers: [
    UserService,
    IpService,
    ModalService
  ],
  bootstrap: [AppComponent],
  entryComponents: [ConfirmationModalComponent]
})
export class AppModule { }
