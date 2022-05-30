import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class DeminimisProvider {
    contentType: any;
    apiUrl: any;

    constructor(public http: HttpClient) {
        // console.log('Hello DeminimisProvider Provider');
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }
    
    getDeminimis() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/deminimis', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }
}
