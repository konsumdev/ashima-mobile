import { Component, OnInit } from '@angular/core';
import { Geolocation } from '@ionic-native/geolocation/ngx';
import { NativeGeocoder, NativeGeocoderResult, NativeGeocoderOptions } from '@ionic-native/native-geocoder/ngx';
import {
    GoogleMaps,
    GoogleMap,
    GoogleMapsEvent,
    GoogleMapOptions,
    Marker,
} from '@ionic-native/google-maps';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { Platform, LoadingController } from '@ionic/angular';
import { HoursProvider } from 'src/app/services/hours/hours';
import { Camera, CameraOptions } from '@ionic-native/camera/ngx';
import { FileTransfer, FileUploadOptions, FileTransferObject } from '@ionic-native/file-transfer/ngx';
import { AppComponent } from 'src/app/app.component';
import * as API_CONFIG from '../../services/api-config';

@Component({
    selector: 'app-clockin',
    templateUrl: './clockin.page.html',
    styleUrls: ['./clockin.page.scss'],
})
export class ClockinPage implements OnInit {
    map: GoogleMap;
    myDate: String = new Date().toISOString();
    time: Date = new Date();
    canUseClockin: boolean = false;
    isGPSOn: boolean = false;
    enablePhoto: boolean = false;
    latLng: any;
    locAddress: any;

    noNeedClockin: boolean = false;
    isAuthorizedCamera: boolean = false;
    apiUrl: any;

    imageURI: any;
    empId: any;
    accId: any;
    compId: any;
    psaId: any;
    cloudId: any;
    coorLong: any;
    coorLat: any;

    constructor(
        private geolocation: Geolocation,
        private nativeGeocoder: NativeGeocoder,
        public platform: Platform,
        private hoursProv: HoursProvider,
        private camera: Camera,
        private transfer: FileTransfer,
        private mainApp : AppComponent,
        public comCtrl: MyCommonServices,
        public loadingCtrl: LoadingController,
    ) { 
        this.apiUrl = API_CONFIG.API.URL;
    }

    ngOnInit() {
        
        setInterval(() => {
        this.time = new Date();
        }, 1);
        
        this.platform.ready().then(()=> {
        this.checkClockGuard();
        this.loadMap();      
        });

        this.mainApp.apiSessionChecker().then(data=>{
            
            if (data) {
                let rslt: any = data;
                this.empId = rslt.emp_id;
                this.accId = rslt.account_id;
                this.compId = rslt.comp_id;
                this.psaId = rslt.psa_id;
                this.cloudId = rslt.cloud_id;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    loadMap() {
        
        this.isGPSOn = true;

        let options = {
            enableHighAccuracy: true,
            timeout: 25000
        };
        
        this.geolocation.getCurrentPosition().then((resp) => {
        
        let mapOptions: GoogleMapOptions = {
            camera: {
            target: {
                lat: resp.coords.latitude,
                lng: resp.coords.longitude
            },
            zoom: 18,
            tilt: 30
            }
        };

        this.coorLong = resp.coords.longitude;
        this.coorLat = resp.coords.latitude;
    
        this.map = GoogleMaps.create('map_canvas', mapOptions);
        if (this.map) {
            this.generateLocation(resp.coords.latitude, resp.coords.longitude);
        }       
        }).catch((error) => {
            // this.comCtrl.presentToast("err: "+JSON.stringify(error), "error");
            console.log('Error getting location', error);
        });
    }

    checkClockGuard() {
        this.comCtrl.presentLoading('', 'empty-spinner-ios');
        this.hoursProv.clockGuardSettings().then(res=>{
            console.log(res);
            let rslt: any = res;
            if (rslt) {
                if (rslt.no_need_clockin) {
                    this.noNeedClockin = false;
                }
                if (!rslt.mobile_clockin) {
                    this.canUseClockin = false;
                } else {
                    this.canUseClockin = true;
                }
                if (rslt.photo_capture) {
                    this.enablePhoto = true;
                }
            }
            
        }).catch(err=>{
            console.log(err);
        });
    }

    dropPin(lat: any, lang: any) {
        let marker: Marker = this.map.addMarkerSync({
        title: 'Ionic',
        icon: 'blue',
        animation: 'DROP',
        position: {
            lat: lat,
            lng: lang
        }
        });
        marker.on(GoogleMapsEvent.MARKER_CLICK).subscribe(() => {
        alert('You are here');
        });
    }

    generateLocation(lat: any, lang: any) {
        
        this.map.clear();
            
        let options: NativeGeocoderOptions = {
            useLocale: true,
            maxResults: 5
        };
        
        this.nativeGeocoder.reverseGeocode(lat, lang, options)
            .then((result: NativeGeocoderResult[]) =>  {
                
                let resultGeoCode = result[0];
                let street = resultGeoCode.thoroughfare;
                let barangay = resultGeoCode.subLocality;
                let city = resultGeoCode.locality;
                let province = resultGeoCode.administrativeArea;
                this.locAddress = "";
                let addArray: any = [];
                if (street) {
                    addArray.push(street);
                }
                if (barangay) {
                    addArray.push(barangay);
                }
                if (city) {
                    addArray.push(city);
                }
                if (province) {
                    addArray.push(province);
                }
                this.locAddress = addArray.join(", ");

                let marker: Marker = this.map.addMarkerSync({
                    title: this.locAddress,
                    animation: 'DROP',
                    position: {
                        lat: lat,
                        lng: lang
                    }
                });
        
                marker.showInfoWindow();
        
                marker.on(GoogleMapsEvent.MARKER_CLICK).subscribe(() => {
                    this.comCtrl.presentToast("You are here.", "");
                });
            })
            .catch((error: any) => {
                // this.comCtrl.presentToast("err: "+JSON.stringify(error), "error");
                console.log(error)
            });
    }

    testNativeCamera() {
        const options: CameraOptions = {
        quality: 20,
        targetWidth: 360,
        targetHeight: 480,
        correctOrientation: true,
        destinationType: this.camera.DestinationType.DATA_URL,
        encodingType: this.camera.EncodingType.JPEG,
        mediaType: this.camera.MediaType.PICTURE,
        cameraDirection: this.camera.Direction.FRONT
        }
        
        this.camera.getPicture(options).then((imageData) => {
            this.imageURI = 'data:image/jpeg;base64,' + imageData;
            this.uploadImage();
        }, (err) => {
            console.log(err);
        });
    }

    noFotoClockin() {
        this.comCtrl.presentLoadingDefault();

        this.hoursProv.clockIn(this.locAddress).then(res=>{
        // this.hoursProv.clockIn(this.locAddress, this.empId, this.accId, this.compId, this.psaId, this.cloudId, this.coorLat, this.coorLong).then(res=>{
            console.log(res);
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.comCtrl.presentToast('You have successfully punched in/out.', 'success');
                } else {
                    this.comCtrl.presentToast('' + rslt.error_msg, 'error');
                }
            } else {
                this.comCtrl.presentToast('', 'api_error');
            }
            this.loadingCtrl.dismiss();
        }).catch(err=>{
            this.loadingCtrl.dismiss();
            this.comCtrl.presentToast('', 'api_error');
        });
    }

    uploadImage() {
        
        const fileTransfer: FileTransferObject = this.transfer.create();

        let options: FileUploadOptions = {
            fileKey: 'file',
            fileName: 'ionicfile',
            mimeType: "image/jpeg",
            headers: {withCredentials: true},
            params : {
                'location' : this.locAddress,
                'emp_id' : this.empId,
                'account_id' : this.accId,
                'company_id' : this.compId,
                'psa_id' : this.psaId,
                'cloud_id' : this.cloudId,
                'longitude' : this.coorLong,
                'latitude' : this.coorLat
            },
            httpMethod : 'POST'
        };

        console.log(this.imageURI);

        fileTransfer.upload(this.imageURI, encodeURI(this.apiUrl + '/api/attendance/clock_in_v2'), options)
            .then((data) => {
                this.comCtrl.presentToast('Upload return.', '', 2000);
                if (data) {
                    
                    let rslt: any = JSON.parse(data.response);
                    
                    if (rslt) {
                        
                        if (rslt.result) {
                            this.comCtrl.presentToast('Time log captured.', 'success');                            
                        } else {
                            this.comCtrl.presentToast('' + rslt.error_msg, 'error');
                        }
                    } else {
                        this.comCtrl.presentToast('', 'api_error', 2000);
                    }
                } else {
                this.comCtrl.presentToast('', 'api_error', 2000);
                }            
                
            }, (err) => {
                this.comCtrl.presentToast('', 'api_error', 2000);
                console.log(err);
        });
    }
}
