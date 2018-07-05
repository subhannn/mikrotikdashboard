import { Component, OnInit } from '@angular/core';

import { IpService } from '../services/ip.service';
import { ModalService } from '../services/modal.service';

@Component({
  selector: 'app-tunnel',
  templateUrl: './tunnel.component.html',
  styleUrls: ['./tunnel.component.css']
})
export class TunnelComponent implements OnInit {

  constructor(
  	private ipService: IpService,
  	private modal: ModalService,
  ) { }

  tunnel: Tunnel;

  loading: Boolean = false;

  ngOnInit() {
    this.loading = true
  	this.ipService.apiService.request('onGetData', {
  		type: 'tunnel_ip'
  	})
  		.subscribe(
  			response => (this.setListTunnelIp(response), this.loading = false)
  		)
  }

  onClick(){
  	this.loading = true
  	if(this.tunnel.max_child_account > 0){
  		this.ipService.apiService.request('onPostData', {
	  		type: 'new_tunnel_user'
	  	})
	  		.subscribe(
	  			response => (this.setListTunnelIp(response), this.loading = false)
	  		)
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

  onChangeStatus(index, event: Event){
    if(typeof this.tunnel.child[index] == 'undefined')
      return false;
    this.loading = true
    var child = this.tunnel.child[index]
    if(event['checked']){
      var action = 'enabled_child_user'
    }else{
      var action = 'disabled_child_user'
    }
    this.ipService.apiService.request('onPostData', {
      type: action,
      data: {
        id: child['id']
      }
    })
    .subscribe(
      response => (this.loading = false)
    )
  }

  private deleteItem(index){
  	if(typeof this.tunnel.child[index] != 'undefined'){
  		this.loading = true
      var child = this.tunnel.child[index];
  		this.ipService.apiService.request('onPostData', {
  			type: 'remove_child_user',
        data: {
          id: child['id']
        }
  		})
  		.subscribe(
  			response => (this.tunnel.child.splice(index,1), this.loading = false, this.tunnel.max_child_account++ )
  		)
  	}
  }
  
  private setListTunnelIp(response){
  	this.tunnel = response
  }
}

export class Tunnel {
	child: Child[];
	max_child_account: number = 0;
	root_account: Object
}