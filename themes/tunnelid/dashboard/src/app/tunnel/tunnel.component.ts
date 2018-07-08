import { Component, OnInit } from '@angular/core';
import { NgxPermissionsService } from 'ngx-permissions';

import { ApiService, Config } from '../services/api.service';
import { ModalService } from '../services/modal.service';
import { UserService } from '../services/user.service';
import { MatSlideToggleChange } from '@angular/material'
import { InlineEvent } from '../components/inline-edit/inline-edit.component';

@Component({
  selector: 'app-tunnel',
  templateUrl: './tunnel.component.html',
  styleUrls: ['./tunnel.component.css']
})
export class TunnelComponent implements OnInit {

  constructor(
    private apiService: ApiService,
    private permissionsService: NgxPermissionsService,
  	private modal: ModalService,
    public userService: UserService
  ) { }

  tunnel: Tunnel;

  ngOnInit() {
  	this.apiService.request('onGetData', <Config>{
      data: {
        type: 'tunnel_ip'
      },
      success: (response) => {
        this.setListTunnelIp(response)
      }
  	})
  }

  onClick(){
  	if(this.tunnel.max_child_account > 0){
  		this.apiService.request('onPostData', <Config>{
        data: {
          type: 'new_tunnel_user'
        },
        success: (response) => {
          this.setListTunnelIp(response)
        }
	  	})
  	}
  }

  onDelete(index){
  	this.modal.confirmation('Are you sure to delete this?')
  		.subscribe(result => {
  			if(result == true){
  				this.deleteItem(index)
  			}
  		})
  }

  onChangeRootPassword(event: InlineEvent){
    this.apiService.request('onPostData', <Config>{
      data: {
        type: 'change_tunnel_password',
        data: {
          type: 'root',
          id: this.tunnel.root_account['id'],
          password: event.text
        }
      },
      success: (response) => {
        this.tunnel.root_account['password'] = event.text
      },
      error: (error) => {
          event.source.reset()
        }
    })
  }

  onChangeChildPassword(event: InlineEvent, index){
    if(typeof this.tunnel.child[index] != 'undefined'){
      this.apiService.request('onPostData', <Config>{
        data: {
          type: 'change_tunnel_password',
          data: {
            type: 'child',
            id: this.tunnel.child[index]['id'],
            password: event.text
          }
        },
        success: (response) => {
          this.tunnel.child[index]['password'] = event.text
        },
        error: (error) => {
          event.source.reset()
        }
      })
    }
  }

  onChangeStatus(index, event: MatSlideToggleChange){
    if(typeof this.tunnel.child[index] == 'undefined')
      return false;

    var child = this.tunnel.child[index]
    if(event['checked']){
      var action = 'enabled_child_user'
    }else{
      var action = 'disabled_child_user'
    }
    this.apiService.request('onPostData', <Config>{
      data: {
        type: action,
        data: {
          id: child['id']
        }
      },
      success: (response) => {
        
      },
      error: (error) => {
        this.tunnel.child[index]['status'] = event['checked']?0:1
        event.source.checked = event['checked']?false:true
      }
    })
  }

  private deleteItem(index){
  	if(typeof this.tunnel.child[index] != 'undefined'){
      var child = this.tunnel.child[index];
  		this.apiService.request('onPostData', <Config>{
  			data: {
          type: 'remove_child_user',
          data: {
            id: child['id']
          }
        },
        success: (response) => {
          this.tunnel.child.splice(index,1)
          this.tunnel.max_child_account++ 
        }
  		})
  	}
  }
  
  private setListTunnelIp(response){
  	this.tunnel = response
  }
}

export class Tunnel {
	child: Object[];
	max_child_account: number = 0;
	root_account: Object
}