# To change this template, choose Tools | Templates
# and open the template in the editor.
require 'rubygems'
require 'json'

class Ussdreq
  include HTTParty
  default_params :output => 'json'
  format :json

  def initialize(baseuri, appid, pass)
    #@auth = {:username => u, :password => p}
    @app_id = appid
    @passwd = pass
    @base_uri = baseuri
  end

  def replyussd(msisdn,text)

    @result = HTTParty.post(@base_uri.to_str,
    :body => { :applicationId => @app_id.to_str,
                :password => @passwd.to_str,
                :message => text,
                :sessionId => "1330929317043",
                :ussdOperation => "mt-cont",
                :destinationAddress => "tel:94771122336"
             }.to_json,
    :headers => { 'Content-Type' => 'application/json' })
  end

  def deliverussd(msisdn,text)

    @result = HTTParty.post(@base_uri.to_str,
    :body => { :statusCode => "S1000",
               :statusDetail =>"Success"
             }.to_json,
    :headers => { 'Content-Type' => 'application/json' })
  end
end