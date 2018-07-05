import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
 
import { Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';
import * as $ from 'jquery';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  constructor(
  	private http: HttpClient
  ) { }

  private setHeaders(handler: string, octoberComponent: string = 'mikrotikDashboard'): HttpHeaders {
  	const headersConfig = {
      'x-october-request-handler': octoberComponent+'::'+handler,
      'x-requested-with': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
    };

  	return new HttpHeaders(headersConfig)
  }

  request(handler: string, body: Object = {}, octoberComponent: string = 'mikrotikDashboard'): Observable<any>{
  	return this.http.post(window.location.origin+window.location.pathname, 
  		$.param(body), 
  		{ 
  			headers: this.setHeaders(handler, octoberComponent)
  		})
  		.pipe(catchError(this.handleError(handler)))
  }

  private handleError<T> (operation = 'operation', result?: T) {
    return (error: any): Observable<T> => {

      // TODO: send the error to remote logging infrastructure
      // console.error(error); // log to console instead
      console.log(operation+' = '+error.message)

      // Let the app keep running by returning an empty result.
      return of(result as T);
    };
  }
}
