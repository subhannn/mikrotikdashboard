import { Injectable, EventEmitter } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
 
import { Observable, of, Subscription } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';
import * as $ from 'jquery';
import { ModalService } from './modal.service';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  constructor(
  	private http: HttpClient,
    private modalService: ModalService
  ) { }
  public isLoading = false

  private setHeaders(handler: string, octoberComponent: string = 'mikrotikDashboard'): HttpHeaders {
  	const headersConfig = {
      'x-october-request-handler': octoberComponent+'::'+handler,
      'x-requested-with': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
    };

  	return new HttpHeaders(headersConfig)
  }

  request(handler: string, options: Config){
    $('#pageLoader').show()
  	this.http.post(window.location.origin+window.location.pathname, 
  		options.data?$.param(options.data):{}, 
  		{ 
  			headers: this.setHeaders(handler, options.octoberComponent?options.octoberComponent:'mikrotikDashboard')
  		})
  		.pipe(catchError(this.handleError(handler, options.error)))
      .subscribe(
        response => this.success(options.success, response)
      )
  }

  private success(onSuccess: any, response: any){
    $('#pageLoader').hide()
    if(typeof onSuccess == 'function' && onSuccess && response){
      onSuccess(response)
    }
  }

  private error(onError: any, err: any){
    $('#pageLoader').hide()
    if(typeof onError == 'function' && onError && err){
      onError(err)
    }
  }

  private handleError<T> (operation = 'operation', onError: any, result?: T) {
    return (error: any): Observable<T> => {
      this.error(onError, error)
      this.modalService.alert(error.error, error.name)

      // Let the app keep running by returning an empty result.
      return of(result as T);
    };
  }
}

export class Config{
  data: any;
  octoberComponent: string;
  success: (response)=> void;
  error: (error)=> void;
}