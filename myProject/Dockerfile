FROM dropinbase/angular:latest

WORKDIR /dropinbase
RUN chown application:application /dropinbase -R

ENV Dropinbase_Vendor_Path /vendor

# Copy files from the Dockercontainer to your own project then edit them, defaults are already in pleace for easy startup
# COPY nginx/* /opt/docker/etc/nginx/
# COPY nginx/vhost.common.d/* /opt/docker/etc/nginx/vhost.common.d/
# COPY nginx.conf /etc/nginx/nginx.conf
# COPY php.webdevops.ini /opt/docker/etc/php/php.webdevops.ini

RUN chown application:application /dropinbase/runtime -R
RUN chmod 755 /dropinbase/runtime -R

EXPOSE 80
EXPOSE 443
