import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class CommissionProvider {
    contentType: any;
    apiUrl: any;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        // console.log('Hello CommissionProvider Provider');
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getCommissions() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/commission', {}, {})
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {                
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });          
        });
    }

    getCommissions_a() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/commission', {withCredentials: true}).subscribe(
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
