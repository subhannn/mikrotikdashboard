import { BrowserModule } from '@angular/platform-browser';
import { NgModule, APP_INITIALIZER } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {MatProgressBarModule, MatProgressSpinnerModule, MatDialogModule,MatSlideToggleModule, MatMenuModule,
  MatIconModule,MatButtonModule, MatTooltipModule} from '@angular/material';
import { ClipboardModule } from 'ngx-clipboard';
import { NgxPermissionsModule, NgxPermissionsService } from 'ngx-permissions';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { HeadBarComponent } from './head-bar/head-bar.component';
import { SideBarComponent } from './side-bar/side-bar.component';
import { HomeComponent } from './home/home.component';
import { TunnelComponent } from './tunnel/tunnel.component';

import { UserService, User } from './services/user.service';
import { ModalModule, ModalService } from './services/modal.service';
import { InlineEditComponent } from './components/inline-edit/inline-edit.component';

@NgModule({
  declarations: [
    AppComponent,
    HeadBarComponent,
    SideBarComponent,
    HomeComponent,
    TunnelComponent,
    InlineEditComponent
  ],
  imports: [
    FormsModule, ClipboardModule, NgxPermissionsModule.forRoot(),
    ModalModule,
    BrowserAnimationsModule,
    MatProgressBarModule,MatProgressSpinnerModule,MatDialogModule,MatSlideToggleModule,MatMenuModule,MatIconModule,MatButtonModule,
    MatTooltipModule,

    BrowserModule,
    HttpClientModule,
    AppRoutingModule,
  ],
  providers: [
    UserService,
    ModalService,
    {
      provide: APP_INITIALIZER,
      useFactory: (us: UserService, ps: NgxPermissionsService) => function() {
        us.populate((user) => {})
        return ps.loadPermissions(window['PERMISSIONS']);
      },
      deps: [UserService, NgxPermissionsService],
      multi: true
    }
  ],
  bootstrap: [AppComponent],
})
export class AppModule { }
