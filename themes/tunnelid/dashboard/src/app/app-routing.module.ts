import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RouterModule, Routes } from '@angular/router';
import { NgxPermissionsGuard } from 'ngx-permissions';

import { AppComponent } from './app.component';
import { HomeComponent } from './home/home.component';
import { TunnelComponent } from './tunnel/tunnel.component';

const routes: Routes = [
	{ path: '', component: HomeComponent },
	{ path: 'tunnel', component: TunnelComponent, canActivate: [NgxPermissionsGuard], 
		data: {
			permissions: {
	        	only: 'susbcribe_tunnel',
	        	redirectTo: '/'
	    	},
		} 
	},
];

@NgModule({
  imports: [ RouterModule.forRoot(routes)],
  exports: [ RouterModule ],
  declarations: [],
})
export class AppRoutingModule { }
