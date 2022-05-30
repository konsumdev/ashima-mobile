import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class ProfileProvider {

    apiUrl: string;
    contentType : string;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getEmployeeProfile() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/profile', {}, {})
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

    getEmployeeQr(id : any, comp_id : any) {
        // let headers = {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'};
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/profile/maketh/' + id + "/" + comp_id, {}, {})
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
