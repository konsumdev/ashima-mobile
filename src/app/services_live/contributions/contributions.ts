import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class ContributionsProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getSSS() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/contributions/sss_summary_head', {}, {})
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

    getSSSHistory(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/contributions/sss', postParams, headers).then(data => {
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

    getSSS_a() {
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

    getSSSHistory_a(page: string) {
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
            this.httpr.get(this.apiUrl + '/api/contributions/phic_summary_head', {}, {})
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

    getPHIC_a() {
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
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/contributions/phic', postParams, headers)
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

    getPHICHistory_a(page: string) {
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
            this.httpr.get(this.apiUrl + '/api/contributions/hdmfm_summary_head', {}, {})
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

    getHDMF_a() {
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
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/contributions/hdmfm', postParams, headers)
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

    getHDMFMHistory_a(page: string) {
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
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/contributions/hdmfv', postParams, headers)
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

    getHDMFVHistory_a(page: string) {
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
