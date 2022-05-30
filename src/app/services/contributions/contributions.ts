import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class ContributionsProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getSSS() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/contributions/sss_summary_head', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getSSSHistory(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/contributions/sss', postParams.toString(), headers).subscribe(
                data => {
                resolve(data);
                }, 
                error => {
                reject(error);
                }
            );
        });
    }

    getPHIC() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/contributions/phic_summary_head', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getPHICHistory(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/contributions/phic', postParams.toString(), headers).subscribe(
                data => {
                resolve(data);
                }, 
                error => {
                reject(error);
                }
            );
        });
    }

    getHDMF() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/contributions/hdmfm_summary_head', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }


    getHDMFMHistory(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/contributions/hdmfm', postParams.toString(), headers).subscribe(
                data => {
                resolve(data);
                }, 
                error => {
                reject(error);
                }
            );
        });
    }

    getHDMFVHistory(page: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('page', page);
        postParams.append('limit', '10');

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/contributions/hdmfv', postParams.toString(), headers).subscribe(
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
