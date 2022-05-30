import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class GovloansProvider {
    contentType: string;
    apiUrl: string;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getGovLoans() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/gov_loan', {}, {})
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

    getThirdLoans() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/third_party_loan', {}, {}).then(data => {
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

    getOtherDeductions(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/other_deductions', postParams, headers)
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
}
